<?php

namespace Modules\Schedule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Training\Models\Teacher;

class TeachingSupportChangeRequestItem extends Model
{
    public const ACTION_ADD = 'add';
    public const ACTION_REMOVE = 'remove';
    public const ACTION_MODIFY = 'modify';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'teaching_support_change_request_items';

    protected $fillable = [
        'change_request_id',
        'support_request_item_id',
        'schedule_slot_id',
        'action',
        'old_snapshot',
        'proposed_snapshot',
        'previous_teacher_id',
        'status',
        'note',
    ];

    protected $casts = [
        'old_snapshot' => 'array',
        'proposed_snapshot' => 'array',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(TeachingSupportChangeRequest::class, 'change_request_id');
    }

    public function supportRequestItem(): BelongsTo
    {
        return $this->belongsTo(TeachingSupportRequestItem::class, 'support_request_item_id');
    }

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class, 'schedule_slot_id');
    }

    public function previousTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'previous_teacher_id');
    }
}
