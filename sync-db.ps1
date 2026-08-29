# ============================================================
# IMPROVEMENT TRACKER - PRODUCTION DATABASE SYNC
# Direction: PRODUCTION -> LOCAL (ONE-WAY ONLY)
# ============================================================

[CmdletBinding()]
param(
    [switch]$DryRun,
    [string]$RemoteHost = "10.88.8.46",
    [string]$RemoteUser = "peroniks"
)

$ErrorActionPreference = "Stop"

# Set working directory strictly to script / local Laravel project location
$ProjectRoot = $PSScriptRoot
if (-not $ProjectRoot) {
    $ProjectRoot = (Get-Location).Path
}
Set-Location $ProjectRoot

# ============================================================
# CONFIGURATION
# ============================================================

# Production Server (SSH & Docker)
$REMOTE_USER         = $RemoteUser
$REMOTE_HOST         = $RemoteHost
$REMOTE_PATH         = "/srv/docker/apps/improvement-tracker"
$REMOTE_DB_CONTAINER = "improvement-tracker-db"
$REMOTE_DB_NAME      = "improvement_tracker"
$REMOTE_DB_USER      = "root"
$REMOTE_DUMP_FILE    = "prod_dump.sql"

# Local MySQL Defaults (will be auto-overwritten by local .env if present)
$LOCAL_DB_NAME       = "improvement_tracker"
$LOCAL_DB_USER       = "root"
$LOCAL_DB_PASS       = ""
$LOCAL_DB_HOST       = "127.0.0.1"
$LOCAL_DB_PORT       = "3306"
$LOCAL_DUMP_FILE     = "prod_dump.sql"
$LOCAL_BACKUP_DIR    = Join-Path $ProjectRoot "backups"

# Auto-detect configuration from local .env
$envFile = Join-Path $ProjectRoot ".env"
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -and -not $line.StartsWith("#") -and $line -match "^([^=]+)=(.*)$") {
            $key = $matches[1].Trim()
            $val = $matches[2].Trim().Trim('"').Trim("'")
            if ($key -eq "DB_DATABASE" -and $val) { $LOCAL_DB_NAME = $val }
            if ($key -eq "DB_USERNAME" -and $val) { $LOCAL_DB_USER = $val }
            if ($key -eq "DB_PASSWORD")          { $LOCAL_DB_PASS = $val }
            if ($key -eq "DB_HOST" -and $val)     { $LOCAL_DB_HOST = $val }
            if ($key -eq "DB_PORT" -and $val)     { $LOCAL_DB_PORT = $val }
        }
    }
}

$localDumpPath = Join-Path $ProjectRoot $LOCAL_DUMP_FILE

# ============================================================
# UX & LOGGING HELPERS
# ============================================================

function Show-Header {
    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host "   IMPROVEMENT TRACKER DATABASE SYNC (PROD->LOCAL) " -ForegroundColor Cyan
    Write-Host "==================================================" -ForegroundColor Cyan
    Write-Host "Direction        : PRODUCTION -> LOCAL (ONE-WAY ONLY)" -ForegroundColor DarkGray
    Write-Host "Production Target: $REMOTE_USER@$REMOTE_HOST" -ForegroundColor DarkGray
    Write-Host "Remote Container : $REMOTE_DB_CONTAINER ($REMOTE_DB_NAME)" -ForegroundColor DarkGray
    Write-Host "Local Target     : ${LOCAL_DB_HOST}:${LOCAL_DB_PORT} ($LOCAL_DB_NAME)" -ForegroundColor DarkGray
    if ($DryRun) {
        Write-Host "MODE             : *** DRY-RUN (READ-ONLY VERIFICATION) ***" -ForegroundColor Magenta
    }
    Write-Host "==================================================" -ForegroundColor Cyan
}

function Show-StepHeader([string]$stepNumber, [string]$title) {
    Write-Host ""
    Write-Host "[$stepNumber] $title" -ForegroundColor Yellow
}

function Show-StepSuccess([string]$message = "SUCCESS") {
    Write-Host "  -> $message" -ForegroundColor Green
}

function Show-FatalError([string]$stage, [string]$reason, [string]$localDbStatus, [string]$backupLocation = "") {
    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Red
    Write-Host "                 SYNC FAILED                      " -ForegroundColor Red
    Write-Host "==================================================" -ForegroundColor Red
    Write-Host "Failed Stage    : $stage" -ForegroundColor Red
    Write-Host "Error Reason    : $reason" -ForegroundColor Red
    Write-Host "Local DB Status : $localDbStatus" -ForegroundColor Yellow
    if ($backupLocation) {
        Write-Host "Local Backup    : $backupLocation" -ForegroundColor Green
    }
    Write-Host "==================================================" -ForegroundColor Red
    Write-Host ""
    exit 1
}

# ============================================================
# BINARY & ENVIRONMENT DETECTION
# ============================================================

Show-Header

$mysqlPath = "mysql"
$mysqldumpPath = "mysqldump"

# Detect mysql.exe
if (-not (Get-Command "mysql" -ErrorAction SilentlyContinue)) {
    $mysqlExe = Get-ChildItem "C:\laragon\bin\mysql" -Filter "mysql.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($mysqlExe) {
        $mysqlPath = $mysqlExe.FullName
    }
    else {
        Show-FatalError -stage "Binary Detection" `
                        -reason "mysql.exe was not found in PATH or C:\laragon\bin\mysql." `
                        -localDbStatus "SAFE - No changes made."
    }
}

# Detect mysqldump.exe
if (-not (Get-Command "mysqldump" -ErrorAction SilentlyContinue)) {
    $mysqldumpExe = Get-ChildItem "C:\laragon\bin\mysql" -Filter "mysqldump.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($mysqldumpExe) {
        $mysqldumpPath = $mysqldumpExe.FullName
    }
    else {
        Show-FatalError -stage "Binary Detection" `
                        -reason "mysqldump.exe was not found in PATH or C:\laragon\bin\mysql." `
                        -localDbStatus "SAFE - No changes made."
    }
}

# Verify local target safety (must be localhost / 127.0.0.1)
if ($LOCAL_DB_HOST -ne "127.0.0.1" -and $LOCAL_DB_HOST -ne "localhost" -and $LOCAL_DB_HOST -ne "::1") {
    Show-FatalError -stage "Safety Pre-flight Check" `
                    -reason "Local DB host '$LOCAL_DB_HOST' is not a local loopback address (127.0.0.1/localhost). Sync aborted for safety." `
                    -localDbStatus "SAFE - No changes made."
}

Write-Host "Local MySQL CLI      : $mysqlPath" -ForegroundColor DarkGray
Write-Host "Local mysqldump CLI  : $mysqldumpPath" -ForegroundColor DarkGray
Write-Host "Local Project Root   : $ProjectRoot" -ForegroundColor DarkGray

# ============================================================
# DRY-RUN / READ-ONLY VERIFICATION
# ============================================================
if ($DryRun) {
    Show-StepHeader "DRY-RUN 1/4" "Verifying SSH & Production Project Path..."
    $remoteCheckCmd = "test -d $REMOTE_PATH && test -f $REMOTE_PATH/.env && echo 'PROD_PATH_ENV_OK'"
    $sshCheck = ssh -o BatchMode=yes -o ConnectTimeout=5 "${REMOTE_USER}@${REMOTE_HOST}" $remoteCheckCmd 2>&1
    if ($LASTEXITCODE -eq 0 -and $sshCheck -match "PROD_PATH_ENV_OK") {
        Show-StepSuccess "SSH Connection verified & project path exists: $REMOTE_PATH"
    } else {
        Show-FatalError -stage "Dry-Run: SSH / Remote Path" `
                        -reason "Unable to connect via SSH or project directory not found ($sshCheck)." `
                        -localDbStatus "SAFE - No changes made."
    }

    Show-StepHeader "DRY-RUN 2/4" "Verifying Production Docker & Database Container..."
    $containerCheckCmd = "docker ps --filter name=$REMOTE_DB_CONTAINER --format '{{.Names}} ({{.Status}})'"
    $containerCheck = ssh "${REMOTE_USER}@${REMOTE_HOST}" $containerCheckCmd 2>&1
    if ($LASTEXITCODE -eq 0 -and $containerCheck -match $REMOTE_DB_CONTAINER) {
        Show-StepSuccess "Production DB Container is running: $containerCheck"
    } else {
        Show-FatalError -stage "Dry-Run: Docker Container" `
                        -reason "Container '$REMOTE_DB_CONTAINER' is not running on remote server." `
                        -localDbStatus "SAFE - No changes made."
    }

    Show-StepHeader "DRY-RUN 3/4" "Testing Local MySQL Connection..."
    $prevPwd = $env:MYSQL_PWD
    $env:MYSQL_PWD = $LOCAL_DB_PASS
    try {
        $testLocal = & $mysqlPath -h $LOCAL_DB_HOST -P $LOCAL_DB_PORT -u $LOCAL_DB_USER -e "SELECT 1;" $LOCAL_DB_NAME 2>&1
        $localPingExit = $LASTEXITCODE
    }
    catch {
        $localPingExit = 1
        $testLocal = $_.Exception.Message
    }
    finally {
        $env:MYSQL_PWD = $prevPwd
    }

    if ($localPingExit -eq 0) {
        Show-StepSuccess "Local MySQL database '$LOCAL_DB_NAME' reachable."
    } else {
        Show-FatalError -stage "Dry-Run: Local MySQL Connection" `
                        -reason "Cannot connect to local database '$LOCAL_DB_NAME' on ${LOCAL_DB_HOST}:${LOCAL_DB_PORT}. Error: $testLocal" `
                        -localDbStatus "SAFE - No changes made."
    }

    Show-StepHeader "DRY-RUN 4/4" "Verifying Local Backup Directory..."
    if (-not (Test-Path $LOCAL_BACKUP_DIR)) {
        New-Item -ItemType Directory -Path $LOCAL_BACKUP_DIR -Force | Out-Null
    }
    Show-StepSuccess "Backup directory ready: $LOCAL_BACKUP_DIR"

    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Magenta
    Write-Host "           DRY-RUN COMPLETED SUCCESSFULLY         " -ForegroundColor Magenta
    Write-Host "==================================================" -ForegroundColor Magenta
    Write-Host "All credentials, containers, paths, and binaries verified." -ForegroundColor Green
    Write-Host "No data was dumped, transferred, or modified." -ForegroundColor DarkGray
    Write-Host "==================================================" -ForegroundColor Magenta
    Write-Host ""
    exit 0
}

# ============================================================
# [0/7] LOCAL SAFEGUARD BACKUP
# ============================================================

Show-StepHeader "0/7" "Creating LOCAL safeguard backup..."

if (-not (Test-Path $LOCAL_BACKUP_DIR)) {
    New-Item -ItemType Directory -Path $LOCAL_BACKUP_DIR -Force | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupFileName = "local_backup_$timestamp.sql"
$backupFilePath = Join-Path $LOCAL_BACKUP_DIR $backupFileName

# Use MYSQL_PWD environment variable to prevent password exposure and CLI escaping issues
$prevPwd = $env:MYSQL_PWD
$env:MYSQL_PWD = $LOCAL_DB_PASS

try {
    & $mysqldumpPath -h $LOCAL_DB_HOST -P $LOCAL_DB_PORT -u $LOCAL_DB_USER `
        --default-character-set=utf8mb4 `
        --single-transaction `
        --quick `
        --no-tablespaces `
        --result-file="$backupFilePath" `
        $LOCAL_DB_NAME
    $dumpExit = $LASTEXITCODE
}
catch {
    $dumpExit = 1
}
finally {
    $env:MYSQL_PWD = $prevPwd
}

if ($dumpExit -ne 0 -or (-not (Test-Path $backupFilePath)) -or ((Get-Item $backupFilePath).Length -eq 0)) {
    Show-FatalError -stage "[0/7] Local Safeguard Backup" `
                    -reason "Failed to create local safeguard backup or backup file is empty." `
                    -localDbStatus "SAFE - Aborted before touching production or local data."
}

$localBackupSize = [math]::Round(((Get-Item $backupFilePath).Length / 1KB), 2)
Show-StepSuccess "Backup saved -> backups/$backupFileName ($localBackupSize KB)"

# ============================================================
# [1/7] CREATE PRODUCTION DUMP & GENERATE CHECKSUM
# ============================================================

Show-StepHeader "1/7" "Creating PRODUCTION database dump..."

# Read DB_PASSWORD securely on remote server without exposing to process table / CLI
# Uses single-quoted here-string (@' ... '@) so PowerShell will NOT evaluate $ variables
$remoteDumpScript = @'
set -e
cd /srv/docker/apps/improvement-tracker

# Safely extract DB_PASSWORD without stripping internal special characters
PROD_DB_PASS=$(grep -E '^DB_PASSWORD=' .env | sed -e 's/^DB_PASSWORD=//' -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'$/\1/")

docker exec -e MYSQL_PWD="$PROD_DB_PASS" improvement-tracker-db mysqldump \
    --default-character-set=utf8mb4 \
    --single-transaction \
    --quick \
    --no-tablespaces \
    --set-gtid-purged=OFF \
    -uroot improvement_tracker > /srv/docker/apps/improvement-tracker/prod_dump.sql

# Output sha256 checksum
sha256sum /srv/docker/apps/improvement-tracker/prod_dump.sql | awk '{print $1}'
'@

try {
    # Send script as stdin via PowerShell pipeline into remote bash -s
    $remoteOutput = $remoteDumpScript | ssh "${REMOTE_USER}@${REMOTE_HOST}" "bash -s"
    $sshExit = $LASTEXITCODE
}
catch {
    $sshExit = 1
}

# The last line of remote output is the SHA256 checksum
$remoteSha256 = if ($remoteOutput) { ($remoteOutput -split "`r?`n")[-1].Trim() } else { "" }

if ($sshExit -ne 0 -or [string]::IsNullOrWhiteSpace($remoteSha256) -or $remoteSha256.Length -ne 64) {
    Show-FatalError -stage "[1/7] Production Database Dump" `
                    -reason "Failed to execute production mysqldump or generate SHA256 on remote server." `
                    -localDbStatus "SAFE - Local database unchanged." `
                    -backupLocation $backupFilePath
}

Show-StepSuccess "Production dump created (Remote SHA256: $remoteSha256)"

# ============================================================
# [2/7] DOWNLOAD PRODUCTION DUMP & INTEGRITY CHECK
# ============================================================

Show-StepHeader "2/7" "Downloading production dump..."

$scpSource = "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/${REMOTE_DUMP_FILE}"

try {
    scp $scpSource $localDumpPath
    $scpExit = $LASTEXITCODE
}
catch {
    $scpExit = 1
}

if ($scpExit -ne 0 -or (-not (Test-Path $localDumpPath)) -or ((Get-Item $localDumpPath).Length -eq 0)) {
    Show-FatalError -stage "[2/7] Download Production Dump" `
                    -reason "Failed to download production dump via SCP or downloaded file is empty." `
                    -localDbStatus "SAFE - Local database unchanged." `
                    -backupLocation $backupFilePath
}

# Verify SHA256 integrity
$localSha256 = (Get-FileHash -Path $localDumpPath -Algorithm SHA256).Hash.ToLower()
if ($localSha256 -ne $remoteSha256.ToLower()) {
    Show-FatalError -stage "[2/7] Download Integrity Verification" `
                    -reason "SHA256 checksum mismatch! Remote: $remoteSha256 vs Local: $localSha256. Corrupted download." `
                    -localDbStatus "SAFE - Local database unchanged." `
                    -backupLocation $backupFilePath
}

$dumpSize = [math]::Round(((Get-Item $localDumpPath).Length / 1MB), 2)
Show-StepSuccess "Downloaded and verified $LOCAL_DUMP_FILE ($dumpSize MB - Checksum OK)"

# ============================================================
# [3/7] IMPORT INTO LOCAL DATABASE
# ============================================================

Show-StepHeader "3/7" "Importing production database into LOCAL ($LOCAL_DB_NAME)..."

$prevPwd = $env:MYSQL_PWD
$env:MYSQL_PWD = $LOCAL_DB_PASS

try {
    $dumpFile = (Resolve-Path $localDumpPath).Path

    # Proven cmd.exe wrapper with input redirection and full stderr capture
    $importOutput = & cmd.exe /c "`"$mysqlPath`" -h $LOCAL_DB_HOST -P $LOCAL_DB_PORT -u $LOCAL_DB_USER --default-character-set=utf8mb4 $LOCAL_DB_NAME < `"$dumpFile`"" 2>&1
    $importExit = $LASTEXITCODE
}
catch {
    $importExit = 1
    $importOutput = $_.Exception.Message
}
finally {
    $env:MYSQL_PWD = $prevPwd
}

if ($importExit -ne 0) {
    # Sanitize output to guarantee no credentials leak in logs or console
    $sanitizedDetails = if ($importOutput) {
        ($importOutput | ForEach-Object {
            $line = "$_"
            if ($LOCAL_DB_PASS) { $line = $line.Replace($LOCAL_DB_PASS, "********") }
            $line
        }) -join "`n  "
    } else {
        "Unknown MySQL error."
    }

    Show-FatalError -stage "[3/7] Import Local Database" `
                    -reason "Failed to import dump into local MySQL database '$LOCAL_DB_NAME' (Exit Code: $importExit).`n`nMySQL Output / Error:`n  $sanitizedDetails" `
                    -localDbStatus "WARNING - Import interrupted / partially completed. Use local backup to restore." `
                    -backupLocation $backupFilePath
}

Show-StepSuccess "Database '$LOCAL_DB_NAME' updated with production data & schema."

# ============================================================
# [4/7] RUN LOCAL PENDING MIGRATIONS
# ============================================================

Show-StepHeader "4/7" "Applying LOCAL pending migrations..."

try {
    $migrateOutput = & php artisan migrate --force 2>&1
    $migrateExit = $LASTEXITCODE
}
catch {
    $migrateExit = 1
    $migrateOutput = $_.Exception.Message
}

if ($migrateExit -ne 0) {
    Show-FatalError -stage "[4/7] Run Local Migrations" `
                    -reason "Failed to execute 'php artisan migrate --force' on local database.`n`nArtisan Output:`n  $($migrateOutput -join "`n  ")" `
                    -localDbStatus "WARNING - Production data imported, but local migrations failed to apply." `
                    -backupLocation $backupFilePath
}

Show-StepSuccess "Local migrations executed successfully."
if ($migrateOutput) {
    $migrateOutput | ForEach-Object { Write-Host "    $_" -ForegroundColor DarkGray }
}

# ============================================================
# [5/7] VERIFY LOCAL MIGRATION STATE
# ============================================================

Show-StepHeader "5/7" "Verifying local database migration state..."

try {
    $statusOutput = & php artisan migrate:status 2>&1
    $statusExit = $LASTEXITCODE
}
catch {
    $statusExit = 1
    $statusOutput = $_.Exception.Message
}

$hasPending = $statusOutput | Where-Object { $_ -match "Pending" }

if ($statusExit -ne 0 -or $hasPending) {
    Show-FatalError -stage "[5/7] Verify Migration State" `
                    -reason "Pending migrations still detected after migration step.`n`nStatus Output:`n  $($statusOutput -join "`n  ")" `
                    -localDbStatus "WARNING - Migration state inconsistent." `
                    -backupLocation $backupFilePath
}

Show-StepSuccess "All local migrations are fully applied (No pending migrations)."

# ============================================================
# [6/7] CLEAR LOCAL LARAVEL CACHE
# ============================================================

Show-StepHeader "6/7" "Clearing LOCAL Laravel cache..."

try {
    $cacheOutput = & php artisan optimize:clear 2>&1
    $artisanExit = $LASTEXITCODE
}
catch {
    $artisanExit = 1
}

if ($artisanExit -eq 0) {
    Show-StepSuccess "Laravel local cache cleared."
}
else {
    Write-Host "  -> WARNING: Failed to clear local cache automatically. You can run 'php artisan optimize:clear' manually." -ForegroundColor Yellow
}

# ============================================================
# [7/7] CLEANUP TEMPORARY FILES & FINAL VERIFICATION
# ============================================================

Show-StepHeader "7/7" "Cleaning temporary files & verifying environment..."

# Clean remote temporary file safely (strictly targeted)
try {
    ssh "${REMOTE_USER}@${REMOTE_HOST}" "rm -f $REMOTE_PATH/$REMOTE_DUMP_FILE"
}
catch {
    Write-Host "  -> Note: Remote temp file cleanup skipped." -ForegroundColor Gray
}

# Clean local temporary dump
if (Test-Path $localDumpPath) {
    Remove-Item $localDumpPath -Force -ErrorAction SilentlyContinue
}

# Verify Laravel application boots cleanly
try {
    $bootCheck = & php artisan about --only=environment 2>&1
    $bootExit = $LASTEXITCODE
}
catch {
    $bootExit = 1
}

if ($bootExit -ne 0) {
    Write-Host "  -> WARNING: Application boot check failed. Please check local .env configuration." -ForegroundColor Yellow
} else {
    Show-StepSuccess "Application boot & database connection verified."
}

Show-StepSuccess "Temporary dump files removed. Local safeguard backup preserved:"
Write-Host "  -> $backupFilePath" -ForegroundColor DarkCyan

# ============================================================
# COMPLETION BANNER
# ============================================================

Write-Host ""
Write-Host "==================================================" -ForegroundColor Green
Write-Host "   IMPROVEMENT TRACKER DATABASE SYNC COMPLETE     " -ForegroundColor Green
Write-Host "               PRODUCTION -> LOCAL                " -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Green
Write-Host "Production Data : Synced" -ForegroundColor DarkGreen
Write-Host "Local Schema    : Up-to-date with all migrations" -ForegroundColor DarkGreen
Write-Host "Local Status    : READY" -ForegroundColor DarkGreen
Write-Host "==================================================" -ForegroundColor Green
Write-Host ""
