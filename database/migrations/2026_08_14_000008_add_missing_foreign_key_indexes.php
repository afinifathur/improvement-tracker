<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('department_id', 'users_department_id_index');
            $table->index('manager_id', 'users_manager_id_index');
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->index('department_id', 'daily_reports_department_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropIndex('daily_reports_department_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_manager_id_index');
            $table->dropIndex('users_department_id_index');
        });
    }
};
