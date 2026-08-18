<?php

namespace App\Models;

use App\Enums\Position;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AreaAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id',
        'user_id',
        'role',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'role' => Position::class,
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the assignment covers the given date.
     *
     * ended_at is inclusive: the assignment is active on date D when
     * started_at <= D AND (ended_at IS NULL OR ended_at >= D).
     */
    public function activeOn(Carbon $date): bool
    {
        $day = $date->copy()->startOfDay();

        if ($this->started_at && $this->started_at->copy()->startOfDay()->greaterThan($day)) {
            return false;
        }

        if ($this->ended_at === null) {
            return true;
        }

        return $this->ended_at->copy()->startOfDay()->greaterThanOrEqualTo($day);
    }

    public function scopeActiveOn($query, $date)
    {
        $day = Carbon::parse($date)->startOfDay();

        return $query->where('started_at', '<=', $day)
            ->where(fn ($q) => $q->whereNull('ended_at')->orWhere('ended_at', '>=', $day));
    }
}
