<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WeeklyPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'expected_output',
        'category',
        'category_corrected',
        'impact_level',
        'week_start_date',
        'week_end_date',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
    ];

    /**
     * Get the supervisor (SPV) that owns the plan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the proofs for the weekly plan.
     */
    public function proofs(): HasMany
    {
        return $this->hasMany(PlanProof::class);
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class);
    }

    /**
     * Get the score associated with the weekly plan.
     */
    public function score(): HasOne
    {
        return $this->hasOne(PlanScore::class);
    }

    /**
     * Get the user who created the plan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the plan.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
