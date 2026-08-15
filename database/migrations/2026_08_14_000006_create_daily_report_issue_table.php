<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_report_issue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('daily_reports')->cascadeOnDelete();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('reported_at');

            $table->unique(['daily_report_id', 'issue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_issue');
    }
};
