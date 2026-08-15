<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->date('original_start_date');
            $table->date('original_end_date');
            $table->date('planned_start_date');
            $table->date('planned_end_date');

            $table->string('status')->default('not_started');

            $table->timestamp('completed_at')->nullable();

            $table->string('blocked_reason')->nullable();
            $table->text('blocked_reason_note')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->foreignId('blocked_by_department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->string('cancel_reason')->nullable();
            $table->text('cancel_reason_note')->nullable();

            $table->foreignId('carried_from_id')->nullable()->constrained('work_items')->nullOnDelete();
            $table->foreignId('source_daily_report_id')->nullable()->constrained('daily_reports')->nullOnDelete();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['owner_id', 'planned_start_date']);
            $table->index('status');
            $table->index('planned_end_date');
            $table->index('carried_from_id');
            $table->index('blocked_by_department_id');
            $table->index('department_id');
            $table->index('source_daily_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_items');
    }
};
