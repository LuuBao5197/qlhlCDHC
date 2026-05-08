<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Schedule\Models\ScheduleSlot;

class ChangeRequestItem extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory> */
    use HasFactory;

    protected $fillable = [
        'change_request_id',
        'schedule_slot_id',
        'old_payload',
        'new_payload',
        'apply_status',
        'apply_error',
        'applied_at',
    ];

    protected $casts = [
        'old_payload' => 'array',
        'new_payload' => 'array',
        'applied_at' => 'datetime',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class);
    }
}
