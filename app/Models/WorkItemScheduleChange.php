<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkItemScheduleChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_item_id',
        'old_start_date',
        'old_end_date',
        'new_start_date',
        'new_end_date',
        'reason',
        'reason_note',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'old_start_date' => 'date',
        'old_end_date' => 'date',
        'new_start_date' => 'date',
        'new_end_date' => 'date',
        'changed_at' => 'datetime',
    ];

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
