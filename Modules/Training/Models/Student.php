<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\StudentFactory> */
    use HasFactory;

    protected $fillable = [
        'class_id',
        'student_code',
        'name',
        'date_of_birth',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'class_id');
    }
}
