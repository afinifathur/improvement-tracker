<?php

namespace App\Models;

use App\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Issue extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'department_id',
        'area_id',
        'status',
        'first_reported_at',
        'source_daily_report_id',
        'created_by',
        'updated_by',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    protected $casts = [
        'status' => IssueStatus::class,
        'first_reported_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function sourceDailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'source_daily_report_id');
    }

    public function dailyReports(): BelongsToMany
    {
        return $this->belongsToMany(DailyReport::class, 'daily_report_issue')
            ->withPivot('note', 'reported_at');
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
