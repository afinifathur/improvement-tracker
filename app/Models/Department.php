<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'deactivated_at',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Department $department) {
            if ($department->exists && $department->isDirty('code')) {
                throw new \LogicException('Department code is immutable.');
            }
        });
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function activate(): static
    {
        $this->is_active = true;
        $this->deactivated_at = null;
        $this->save();

        return $this;
    }

    public function deactivate(): static
    {
        $this->is_active = false;
        $this->deactivated_at = now();
        $this->save();

        return $this;
    }

    public function reactivate(): static
    {
        return $this->activate();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class);
    }

    public function blockedWorkItems(): HasMany
    {
        return $this->hasMany(WorkItem::class, 'blocked_by_department_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }
}
