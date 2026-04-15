<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Schedule\Models\ScheduleSlot;

class SubjectLesson extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\SubjectLessonFactory> */
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'lesson_no',
        'title',
        'expected_periods',
        'note',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class);
    }
}
