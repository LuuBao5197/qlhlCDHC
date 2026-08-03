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
        'is_regular_test',
        'expected_periods',
        'note',
    ];

    protected $casts = [
        'is_regular_test' => 'boolean',
    ];

    public function isRegularTest(): bool
    {
        return (bool) $this->is_regular_test;
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class);
    }
}
