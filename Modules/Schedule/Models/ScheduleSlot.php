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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleSlot extends Model
{
    public const ASSIGNMENT_TYPE_SELF_STUDY = 'self_study';

    protected $table = 'schedule_slots';

    protected $fillable = [
        'monthly_schedule_id',
        'schedule_slot_group_id',
        'class_id',
        'teacher_id',
        'assignment_type',
        'assignment_source',
        'teaching_support_request_item_id',
        'subject_id',
        'subject_lesson_id',
        'room_id',
        'slot_type',
        'semester_event_id',
        'event_type',
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

    public function scheduleSlotGroup(): BelongsTo
    {
        return $this->belongsTo(ScheduleSlotGroup::class, 'schedule_slot_group_id');
    }

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function teachingSupportRequestItem(): BelongsTo
    {
        return $this->belongsTo(TeachingSupportRequestItem::class, 'teaching_support_request_item_id');
    }

    public function teachingSupportRequestItems(): HasMany
    {
        return $this->hasMany(TeachingSupportRequestItem::class, 'schedule_slot_id');
    }

    public function teachingSupportChangeRequestItems(): HasMany
    {
        return $this->hasMany(TeachingSupportChangeRequestItem::class, 'schedule_slot_id');
    }

    public function subjectModel(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function subjectLesson(): BelongsTo
    {
        return $this->belongsTo(SubjectLesson::class, 'subject_lesson_id');
    }

    public function semesterEvent(): BelongsTo
    {
        return $this->belongsTo(SemesterEvent::class, 'semester_event_id');
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

    public function departmentMonthlyAssignmentBatches(): BelongsToMany
    {
        return $this->belongsToMany(
            DepartmentMonthlyAssignmentBatch::class,
            'department_monthly_assignment_batch_slots',
            'schedule_slot_id',
            'batch_id'
        )->withTimestamps();
    }
}
