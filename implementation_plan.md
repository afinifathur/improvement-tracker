# Implementation Plan — Restore Docker Deployment Infrastructure

## 1. Current Architecture Findings

| Item | Finding |
|------|---------|
| Laravel | v13 (`laravel/framework ^13.0`), PHP `^8.3`, Sanctum `^4.0` |
| Frontend | Vite 8 + Tailwind 4 + `laravel-vite-plugin` 3. Views use **CDN Tailwind**, do NOT call `@vite()` (per AGENTS.md) |
| DB default | `sqlite` in `.env.example`, but full `mysql` connection exists in `config/database.php` |
| Sessions/Cache/Queue | All set to `database` driver (require a DB up before app boots) |
| Uploads | `public` disk → `storage/app/public/proofs/`; `storage:link` maps `public/storage` |
| Docker | **Absent.** No `Dockerfile`, `docker-compose.yml`, or nginx conf in current HEAD |
| `package-lock.json` | Absent (use `npm install`, not `npm ci`) |

### Old Docker implementation (from git history, commit `ce50f53`)
- `app`: `php:8.3-fpm`, source **bind-mounted** (`.:/var/www/html`), `composer install`/`npm build` done out-of-band (fragile).
- `web`: `nginx:alpine`, `6004:80`, bind-mount code + `docker/nginx/default.conf`, `fastcgi_pass app:9000`.
- `db`: `mysql:8.0`, `MYSQL_DATABASE=${DB_DATABASE}`, `MYSQL_ROOT_PASSWORD=${DB_PASSWORD}`, named volume `dbdata`.
- No healthchecks, no entrypoint, no `.dockerignore`, no asset build inside image.

**Decision:** restore the same 3-service model (nginx + php-fpm + mysql), port `6004`, but improve: bake code/vendor/assets into the image via multi-stage build, add DB healthcheck dependency, add entrypoint for storage dirs + `storage:link`, add persistent volume only for uploads.

### Migration MySQL-compatibility check
- `2026_08_15_000008_drop_daily_reports_reporter_date_unique` already creates a dedicated `reported_by` index **before** `dropUnique`, so it no longer trips MySQL's "needed in a foreign key constraint".
- Unique string indexes (`users.email`, `departments.name/code`, `areas.code`) are `varchar(255)` under `utf8mb4` ≈ 1020 bytes < MySQL 8.0's 3072-byte index limit. OK.
- `enum` columns and `foreignId` (bigint unsigned) are MySQL-safe.
- **No migration changes required.** Confirmed by `docker compose exec app php artisan migrate --force` in verification.

## 2. Proposed Docker Architecture

```
                   ┌──────────────┐
   :6004 ───────►  │  web (nginx) │──fastcgi──►┌───────────────────────┐
                   └──────────────┘  app:9000  │  app (php:8.3-fpm)    │
                                               │  baked code+vendor+   │
                                               │  public/build         │
                                               └──────────┬────────────┘
                                               volume     │  DB via 'db' host
                                             app-storage   ▼
                                          (/storage/app/ ┌──────────────┐
                                           public)        │ db (mysql:8) │
                                                          │ volume dbdata│
                                                          └──────────────┘
```

- Single custom bridge network `improvement-network`.
- MySQL reachable only inside the network (no host port published).
- Only HTTP port `6004` published (matches previous infrastructure).

## 3. Files to Create

| File | Purpose |
|------|---------|
| `Dockerfile` | Multi-stage: (1) `node:22-alpine` builds Vite assets → `public/build`; (2) `php:8.3-fpm-bookworm` installs extensions + Composer, `composer install --no-dev --optimize-autoloader`, copies app + built assets, sets `www-data` ownership. |
| `docker-compose.yml` | Services `app`, `web`, `db`; network `improvement-network`; volumes `dbdata`, `app-storage`. |
| `docker/nginx/default.conf` | Server block: root `public/`, `client_max_body_size 20M`, `fastcgi_pass app:9000`, `try_files → index.php`. |
| `docker/php/php.ini` | `upload_max_filesize=20M`, `post_max_size=20M`, `opcache` enable. |
| `docker/php/entrypoint.sh` | `mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public`, `chown www-data`, `php artisan storage:link`, then `exec php-fpm`. |
| `.dockerignore` | Exclude `.git`, `.env`, `node_modules`, `vendor`, `storage`, `public/build`, `public/storage`, `database/*.sqlite`. |
| `docker/.env.example` | Docker env template (operator copies to project-root `.env`). |

**No application code / migrations / seeders / config changed.**

## 4. Dockerfile Detail (multi-stage)

- **Stage `frontend`** (`node:22-alpine`): copy `package.json`, `vite.config.js`, `resources/`, `public/`; `npm install && npm run build`.
- **Stage `app`** (`php:8.3-fpm-bookworm`):
  - Install `pdo_mysql`, `mbstring`, `zip`, `gd`, `exif`, `opcache`.
  - Copy Composer from `composer:2` image.
  - `COPY . .` → `composer install --no-dev --optimize-autoloader` (scripts run fine; `artisan` present).
  - `COPY --from=frontend /app/public/build ./public/build`.
  - `COPY docker/php/php.ini`, `entrypoint.sh`; `chown -R www-data` on storage + bootstrap/cache.
  - `USER www-data`, `ENTRYPOINT ["docker/php/entrypoint.sh"]`, `EXPOSE 9000`.
- `node_modules` never enters the final image.

## 5. Database Strategy

- Production `DB_CONNECTION=mysql`, host `db` (compose service name), port `3306`.
- Image `mysql:8.0`; env: `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`.
- **Persistence:** named volume `dbdata` → `/var/lib/mysql`. Never delete the volume.
- Backup note: `docker compose exec db mysqldump -u<user> -p <db> > backup.sql`.
- No SQLite anywhere in production.

## 6. Environment Strategy

Template `docker/.env.example` (copy → `.env` at repo root, already gitignored). Keys:

```
APP_NAME=ImprovementTracker
APP_ENV=production
APP_DEBUG=false
APP_KEY=            # generated: php artisan key:generate --show
APP_URL=https://<host>:6004   (or configured domain)

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=improvement_tracker
DB_USERNAME=improvement
DB_PASSWORD=<secret>
MYSQL_ROOT_PASSWORD=<secret>

APP_PORT=6004
```

- `docker-compose.yml` interpolates `${...}` from `.env`; passes app vars into the `app` container and `MYSQL_*` into `db`.
- Real secrets stay out of Git; only the template is committed.
- Session/cache/queue remain `database` (defaults), so they need the DB up (handled by healthcheck dependency).

## 7. Deployment Workflow (server)

```bash
cd /srv/docker/apps/improvement-tracker
git pull origin main
cp docker/.env.example .env          # first time only; fill APP_KEY/DB_*/MYSQL_*
# APP_KEY: docker compose run --rm app php artisan key:generate --show

docker compose build                 # or --no-cache on first build
docker compose up -d

docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

## 8. Seeder Policy

- **No automatic `db:seed`** in the image, entrypoint, or compose.
- **Master data (manual, first deploy only), run individually:**
  ```bash
  docker compose exec app php artisan db:seed --class=DepartmentSeeder --force
  docker compose exec app php artisan db:seed --class=UserSeeder --force
  docker compose exec app php artisan db:seed --class=AuthUserSeeder --force
  ```
- **Never run `DatabaseSeeder` or `DevelopmentDailyReportSeeder` in production** (they create demo daily reports/plans/work items).
- No seeder/code modification needed. (Optional future: a `ProductionSeeder` calling the three master seeders — not created now.)

## 9. Storage Strategy

- Named volume `app-storage` → `/var/www/html/storage/app/public` (persists `proofs/` uploads across container rebuilds).
- `public/storage` symlink is recreated idempotently by the entrypoint via `php artisan storage:link`.
- `storage/framework/*` remains ephemeral (regenerated on start; cache/session/queue live in MySQL anyway).

## 10. Networking / Ports

- Only `6004:80` published (nginx). Configurable via `APP_PORT`.
- MySQL not published to host; reachable only on `improvement-network`.
- `web` depends on `app`; `app` depends on `db` with `condition: service_healthy` (mysqladmin ping healthcheck) to avoid boot-before-DB races.

## 11. Quality / Safety

- No `chmod 777`; ownership handled via `--chown` and `chown -R www-data`.
- Run as `www-data` (non-root) in the app container.
- No hardcoded secrets; no host absolute paths.
- Named volumes for DB + uploads only; no source bind-mount in production.

## 12. Verification Checklist

- [ ] A. `docker-compose.yml` + `Dockerfile` + `docker/nginx/default.conf` present.
- [ ] B. `docker compose build --no-cache` succeeds.
- [ ] C. `docker compose up -d` starts all three containers (`docker compose ps` healthy).
- [ ] D. PHP-FPM up (`docker compose exec app php -v`).
- [ ] E. Nginx reaches PHP-FPM (`curl -I http://localhost:6004/login`).
- [ ] F. Laravel connects to MySQL (`docker compose exec app php artisan db:show`).
- [ ] G. `docker compose exec app php artisan migrate --force` succeeds (incl. the corrected unique-drop migration).
- [ ] H. `/login` returns 200.
- [ ] I. Login works (seed `UserSeeder`/`AuthUserSeeder`; use a seeded account).
- [ ] J. Daily Report workflow (create/read) works.
- [ ] K. Weekly Plan workflow (create/update status → score) works.
- [ ] L. `/this-week` loads.
- [ ] M. `/today` loads.
- [ ] N. Test suite (where runnable in container, dev deps aside): `docker compose run --rm app php artisan test` — app code failures are reported separately, never patched just for Docker.

## 13. Rollback Strategy

1. `git checkout <previous-good-commit>`
2. `docker compose build` and `docker compose up -d`
3. Re-run migrate/cache commands.
- `docker compose down` (no `-v`) to stop without deleting volumes.
- **Never** run `docker system prune -a`, `docker volume prune`, or `docker compose down -v` without explicit approval — this preserves `dbdata`.

## 14. Risks

- **No `package-lock.json`** → `npm install` resolves fresh versions (minor reproducibility risk; Vite 8/Tailwind 4 pinned as `^` ranges).
- **CDN Tailwind views** → `npm run build` output is effectively unused by pages today; build is retained for future `@vite()` adoption and for `public/build` presence.
- **APP_KEY missing** breaks encrypted sessions/cookies → must be set before first real login.
- **Port 6004 conflict** with another app on the host → confirmed against previous config; adjustable via `APP_PORT`.
- **MySQL 8.0 vs Laravel 13** — fully supported; no driver concerns.

## 15. Before touching the server

- Run `docker ps` / `docker compose ps` to inspect currently running containers.
- Do **not** remove existing containers/images/volumes automatically.
- Existing app code on the server is the outdated Docker image `ce50f53`; it will be replaced by the new build, not deleted mid-flight.
