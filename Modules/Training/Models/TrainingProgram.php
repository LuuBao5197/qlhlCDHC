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
        'status',
    ];

    protected $casts = [
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
