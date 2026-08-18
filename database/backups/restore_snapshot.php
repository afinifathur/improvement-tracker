<?php
require 'c:/laragon/www/improvement-tracker/vendor/autoload.php';
$app = require_once 'c:/laragon/www/improvement-tracker/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jsonPath = 'c:/laragon/www/improvement-tracker/database/backups/v1_master_data_snapshot.json';
if (!file_exists($jsonPath)) {
    die("Error: Snapshot file not found at $jsonPath\n");
}

$data = json_decode(file_get_contents($jsonPath), true);

// Disable foreign keys outside the transaction
DB::statement('PRAGMA foreign_keys = OFF;');

try {
    DB::transaction(function () use ($data) {
        // Clean tables
        DB::table('area_assignments')->truncate();
        DB::table('areas')->truncate();
        DB::table('users')->truncate();
        DB::table('departments')->truncate();

        // 1. Restore Departments
        echo "Restoring departments...\n";
        foreach ($data['departments'] as $row) {
            DB::table('departments')->insert($row);
        }

        // 2. Restore Users
        echo "Restoring users...\n";
        foreach ($data['users'] as $row) {
            DB::table('users')->insert($row);
        }

        // 3. Restore Areas
        echo "Restoring areas...\n";
        foreach ($data['areas'] as $row) {
            DB::table('areas')->insert($row);
        }

        // 4. Restore Area Assignments
        echo "Restoring area assignments...\n";
        foreach ($data['area_assignments'] as $row) {
            DB::table('area_assignments')->insert($row);
        }
    });
    echo "Restore completed successfully!\n";
} finally {
    // Re-enable foreign keys
    DB::statement('PRAGMA foreign_keys = ON;');
}
