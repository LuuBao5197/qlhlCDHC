<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Teacher extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\TeacherFactory> */
    use HasFactory;

    protected $fillable = [
        'teacher_code',
        'name',
        'status',
        'department_id',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}