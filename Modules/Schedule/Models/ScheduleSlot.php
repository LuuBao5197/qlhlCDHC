<?php

namespace Modules\Schedule\Models;

use Modules\Training\Models\ChangeRequest;
use Modules\Training\Models\ChangeRequestItem;
use Modules\Training\Models\DailyTrainingLog;
use Modules\Training\Models\Room;
use Modules\Training\Models\SlotEvaluation;
use Modules\Training\Models\Subject;
use Modules\Training\Models\SubjectLesson;
use Modules\Training\Models\Teacher;
use Modules\Training\Models\TrainingClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleSlot extends Model
{
    protected $table = 'schedule_slots';

    protected $fillable = [
        'monthly_schedule_id',
        'class_id',
        'teacher_id',
        'subject_id',
        'subject_lesson_id',
        'room_id',
        'date',
        'day_of_week',
        'period',
        'period_number',
        'subject',
        'content',
        'slot_status',
        'actual_content',
        'note',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function monthlySchedule(): BelongsTo
    {
        return $this->belongsTo(MonthlySchedule::class, 'monthly_schedule_id');
    }

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function subjectModel(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function subjectLesson(): BelongsTo
    {
        return $this->belongsTo(SubjectLesson::class, 'subject_lesson_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function dailyTrainingLogs(): HasMany
    {
        return $this->hasMany(DailyTrainingLog::class);
    }

    public function slotEvaluations(): HasMany
    {
        return $this->hasMany(SlotEvaluation::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class);
    }

    public function changeRequestItems(): HasMany
    {
        return $this->hasMany(ChangeRequestItem::class);
    }
}
