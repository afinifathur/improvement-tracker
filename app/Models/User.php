<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'department_name', 'department_id', 'manager_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
