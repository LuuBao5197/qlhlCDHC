<?php

namespace Modules\Training\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Schedule\Models\ScheduleSlot;

class SlotEvaluation extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\SlotEvaluationFactory> */
    use HasFactory;

    protected $fillable = [
        'schedule_slot_id',
        'evaluator_id',
        'attendance_count',
        'absent_count',
        'rating_level',
        'comment',
    ];

    public function scheduleSlot(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlot::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
