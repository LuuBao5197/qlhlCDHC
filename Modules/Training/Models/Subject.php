<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Schedule\Models\ScheduleSlot;

class Subject extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\SubjectFactory> */
    use HasFactory;

    protected $fillable = [
        'department_id',
        'code',
        'name',
        'total_periods',
        'status',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(SubjectLesson::class);
    }

    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class);
    }

    public function trainingPrograms(): BelongsToMany
    {
        return $this->belongsToMany(TrainingProgram::class, 'subject_training_program')->withTimestamps();
    }

    public function lessonsForTrainingProgram(int $trainingProgramId): HasMany
    {
        return $this->lessons()->where('training_program_id', $trainingProgramId);
    }

    /**
     * Builds the subject code as MãMH_MãKhoa. If the raw code already ends
     * with a known department suffix (e.g. re-submitted from an edit form),
     * that suffix is stripped first so it isn't duplicated.
     *
     * @param iterable<int, string> $existingDepartmentCodes
     */
    public static function composeCode(string $rawCode, string $departmentCode, iterable $existingDepartmentCodes = []): string
    {
        $base = trim($rawCode);

        foreach ($existingDepartmentCodes as $code) {
            $candidateSuffix = '_' . $code;
            if (mb_strlen($base, 'UTF-8') > mb_strlen($candidateSuffix, 'UTF-8')
                && mb_strtolower(mb_substr($base, -mb_strlen($candidateSuffix, 'UTF-8'), null, 'UTF-8'), 'UTF-8') === mb_strtolower($candidateSuffix, 'UTF-8')
            ) {
                $base = mb_substr($base, 0, -mb_strlen($candidateSuffix, 'UTF-8'), 'UTF-8');
                break;
            }
        }

        return $base . '_' . $departmentCode;
    }
}
