<?php

namespace App\Models;

use App\Enums\BlockedReason;
use App\Enums\CancelReason;
use App\Enums\WorkItemStatus;
use App\Enums\WorkType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'owner_id',
        'department_id',
        'area_id',
        'original_start_date',
        'original_end_date',
        'planned_start_date',
        'planned_end_date',
        'status',
        'completed_at',
        'blocked_reason',
        'blocked_reason_note',
        'blocked_at',
        'blocked_by_department_id',
        'cancel_reason',
        'cancel_reason_note',
        'carried_from_id',
        'source_daily_report_id',
        'weekly_plan_id',
        'work_type',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'status' => 'not_started',
    ];

    protected $casts = [
        'original_start_date' => 'date',
        'original_end_date' => 'date',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'completed_at' => 'datetime',
        'blocked_at' => 'datetime',
        'status' => WorkItemStatus::class,
        'blocked_reason' => BlockedReason::class,
        'cancel_reason' => CancelReason::class,
        'work_type' => WorkType::class,
    ];

    protected static function booted(): void
    {
        static::saving(function (WorkItem $item) {
            if ($item->original_start_date && $item->original_end_date
                && $item->original_start_date->greaterThan($item->original_end_date)) {
                throw new \InvalidArgumentException('original_start_date must be on or before original_end_date.');
            }

            if ($item->planned_start_date && $item->planned_end_date
                && $item->planned_start_date->greaterThan($item->planned_end_date)) {
                throw new \InvalidArgumentException('planned_start_date must be on or before planned_end_date.');
            }

            if ($item->exists && ($item->isDirty('original_start_date') || $item->isDirty('original_end_date'))) {
                throw new \LogicException('Original schedule dates are immutable.');
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function blockedByDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'blocked_by_department_id');
    }

    public function sourceDailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'source_daily_report_id');
    }

    public function weeklyPlan(): BelongsTo
    {
        return $this->belongsTo(WeeklyPlan::class);
    }

    public function carriedFrom(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class, 'carried_from_id');
    }

    public function carriedOverItems(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'carried_from_id');
    }

    public function scheduleChanges(): HasMany
    {
        return $this->hasMany(WorkItemScheduleChange::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
