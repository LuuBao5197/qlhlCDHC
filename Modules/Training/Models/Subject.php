<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
