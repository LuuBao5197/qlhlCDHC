<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingProgram extends Model
{
    use HasFactory;

    protected $table = 'training_programs';

    protected $fillable = [
        'code',
        'name',
        'semester_count',
        'status',
    ];

    public const DEFAULT_SEMESTER_COUNT = 2;

    public const MAX_SEMESTER_COUNT = 6;

    protected $casts = [
        'semester_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(TrainingBatch::class, 'training_program_id');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_training_program')->withTimestamps();
    }

    public function subjectLessons(): HasMany
    {
        return $this->hasMany(SubjectLesson::class);
    }
}
