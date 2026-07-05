<?php

namespace Modules\Training\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Schedule\Models\TeachingSupportChangeRequest;
use Modules\Schedule\Models\TeachingSupportRequest;

class Department extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\DepartmentFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
    ];

    public function trainingClasses(): HasMany
    {
        return $this->hasMany(TrainingClass::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function requestedTeachingSupportRequests(): HasMany
    {
        return $this->hasMany(TeachingSupportRequest::class, 'requesting_department_id');
    }

    public function proposedTeachingSupportRequests(): HasMany
    {
        return $this->hasMany(TeachingSupportRequest::class, 'proposed_supporting_department_id');
    }

    public function assignedTeachingSupportRequests(): HasMany
    {
        return $this->hasMany(TeachingSupportRequest::class, 'assigned_supporting_department_id');
    }

    public function teachingSupportChangeRequests(): HasMany
    {
        return $this->hasMany(TeachingSupportChangeRequest::class, 'requesting_department_id');
    }
}
