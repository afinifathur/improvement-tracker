<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'department_name',
    'department_id',
    'manager_id',
    'is_active',
    'deactivated_at',
    'inactive_effective_date',
    'deactivation_reason',
    'deactivation_note',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the weekly plans for the user.
     */
    public function weeklyPlans()
    {
        return $this->hasMany(WeeklyPlan::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function areaAssignments(): HasMany
    {
        return $this->hasMany(AreaAssignment::class);
    }

    public function assignedAreas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'area_assignments')
            ->withPivot('role', 'started_at', 'ended_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Real active operational personnel: active users holding at least one
     * organizational assignment.
     */
    public function scopeOperationalPersonnel($query)
    {
        return $query->where('is_active', true)->whereHas('areaAssignments');
    }

    /**
     * Historical operational personnel: all users (active or inactive)
     * who have held an organizational assignment.
     */
    public function scopeHistoricalPersonnel($query)
    {
        return $query->whereHas('areaAssignments');
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class, 'reported_by');
    }

    public function ownedWorkItems(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'owner_id');
    }

    public function createdDailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class, 'created_by');
    }

    public function updatedDailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class, 'updated_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['manager', 'director']);
    }

    /**
     * Deactivate user with an effective organizational date and close active assignments.
     */
    public function deactivate(?Carbon $effectiveDate = null, ?string $reason = null, ?string $note = null): static
    {
        $effDateStr = $effectiveDate ? $effectiveDate->toDateString() : now()->toDateString();

        return DB::transaction(function () use ($effDateStr, $reason, $note) {
            $this->is_active = false;
            $this->deactivated_at = now();
            $this->inactive_effective_date = $effDateStr;
            $this->deactivation_reason = $reason;
            $this->deactivation_note = $note;
            $this->save();

            // Safely close active area assignments to the effective date
            $this->areaAssignments()
                ->where(function ($q) use ($effDateStr) {
                    $q->whereNull('ended_at')
                        ->orWhere('ended_at', '>', $effDateStr);
                })
                ->update(['ended_at' => $effDateStr]);

            return $this;
        });
    }

    /**
     * Reactivate user. Does not resurrect past closed assignments.
     */
    public function reactivate(): static
    {
        $this->is_active = true;
        $this->deactivated_at = null;
        $this->inactive_effective_date = null;
        $this->deactivation_reason = null;
        $this->deactivation_note = null;
        $this->save();

        return $this;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'inactive_effective_date' => 'date',
        ];
    }
}
