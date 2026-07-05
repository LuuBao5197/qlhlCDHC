<?php

namespace Modules\Schedule\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Training\Models\Teacher;

class TeachingSupportRequestItem extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'teaching_support_request_items';

    protected $fillable = [
        'request_id',
        'schedule_slot_id',
        'status',
        'assigned_teacher_id',
        'assigned_by',
        'assigned_at',
        'note',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(TeachingSupportRequest::class, 'request_id');
    }

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class, 'schedule_slot_id');
    }

    public function assignedTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'assigned_teacher_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function changeItems(): HasMany
    {
        return $this->hasMany(TeachingSupportChangeRequestItem::class, 'support_request_item_id');
    }
}
