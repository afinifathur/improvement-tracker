<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily Report identity is now (reported_by, area_id, report_date).
     *
     * The legacy unique(reported_by, report_date) constraint conflicts with
     * that model: a reporter responsible for two Areas may legitimately submit
     * two reports on the same date. We drop it here. The final
     * unique(reported_by, area_id, report_date) constraint will be added by a
     * dedicated migration AFTER authoritative organizational backfill populates
     * area_id (a UNIQUE constraint cannot be applied while area_id is NULL,
     * because SQL treats NULLs as distinct). Until then, the duplicate rule is
     * enforced at the application/validation layer.
     */
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropUnique(['reported_by', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->unique(['reported_by', 'report_date']);
        });
    }
};
