<?php

namespace Modules\Schedule\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Training\Models\Room;
use Modules\Training\Models\Teacher;

class ScheduleSlotSubgroup extends Model
{
    protected $table = 'schedule_slot_subgroups';

    protected $fillable = [
        'schedule_slot_id',
        'group_label',
        'teacher_id',
        'room_id',
        'note',
        'created_by',
    ];

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class, 'schedule_slot_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
