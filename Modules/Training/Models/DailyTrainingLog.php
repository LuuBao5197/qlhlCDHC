<?php

namespace Modules\Training\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Schedule\Models\ScheduleSlot;

class DailyTrainingLog extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\DailyTrainingLogFactory> */
    use HasFactory;

    protected $fillable = [
        'schedule_slot_id',
        'teacher_id',
        'actual_date',
        'actual_period_number',
        'attendance_count',
        'absent_count',
        'result_status',
        'actual_content',
        'issue_note',
        'remarks',
        'checked_by',
        'checked_at',
    ];

    protected $casts = [
        'actual_date' => 'datetime',
        'checked_at' => 'datetime',
    ];

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
