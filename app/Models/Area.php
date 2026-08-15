<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'department_id',
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
        static::saving(function (Area $area) {
            if ($area->exists && $area->isDirty('code')) {
                throw new \LogicException('Area code is immutable.');
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AreaAssignment::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
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
}
