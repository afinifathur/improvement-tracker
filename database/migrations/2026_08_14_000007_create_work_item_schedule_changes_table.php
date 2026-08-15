<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_item_schedule_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_item_id')->constrained('work_items')->cascadeOnDelete();
            $table->date('old_start_date')->nullable();
            $table->date('old_end_date');
            $table->date('new_start_date');
            $table->date('new_end_date');
            $table->string('reason')->nullable();
            $table->text('reason_note')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['work_item_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_schedule_changes');
    }
};
