<?php

namespace Modules\Training\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;

class ChangeRequest extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\ChangeRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'monthly_schedule_id',
        'schedule_slot_id',
        'requested_by',
        'reason',
        'old_payload',
        'new_payload',
        'status',
        'apply_mode',
        'apply_changes',
        'apply_summary',
        'submitted_at',
        'resolved_at',
    ];

    protected $casts = [
        'old_payload' => 'array',
        'new_payload' => 'array',
        'apply_changes' => 'boolean',
        'apply_summary' => 'array',
        'submitted_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function monthlySchedule(): BelongsTo
    {
        return $this->belongsTo(MonthlySchedule::class);
    }

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function changeRequestItems(): HasMany
    {
        return $this->hasMany(ChangeRequestItem::class);
    }
}
