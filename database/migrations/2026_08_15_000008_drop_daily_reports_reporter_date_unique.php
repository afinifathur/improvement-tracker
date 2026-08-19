<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily Report identity is now:
     * (reported_by, area_id, report_date).
     *
     * The legacy unique(reported_by, report_date) constraint must be removed
     * because one reporter may legitimately have multiple area reports on
     * the same date.
     *
     * MySQL may use the legacy composite unique index to satisfy the
     * reported_by foreign key. Therefore, create a dedicated reported_by
     * index before removing the composite unique index.
     */
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->index('reported_by', 'daily_reports_reported_by_index');
            $table->dropUnique('daily_reports_reported_by_report_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->unique(
                ['reported_by', 'report_date'],
                'daily_reports_reported_by_report_date_unique'
            );

            $table->dropIndex('daily_reports_reported_by_index');
        });
    }
};