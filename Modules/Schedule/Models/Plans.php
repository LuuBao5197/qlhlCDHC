<?php

namespace Modules\Schedule\Models;

use Modules\Training\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Schedule\Models\PlanTemplates;
use Modules\Training\Models\TrainingBatch;

class Plans extends Model
{
    protected $table = 'plans';

    protected $fillable = [
        'training_batch_id',
        'name',
        'semester',
        'year',
        'file_path',
        'description',
        'created_by',
        'submitted_by',
        'submitted_at',
        'status',
        'current_step',
        'effective_from',
        'effective_to',
        'approved_version',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function monthlySchedules()
    {
        return $this->hasMany(MonthlySchedule::class, 'plan_id');
    }

    public function planTemplates()
    {
        return $this->hasMany(PlanTemplates::class, 'plan_id');
    }

    public function semesterEvents(): HasMany
    {
        return $this->hasMany(SemesterEvent::class, 'plan_id');
    }

    public function trainingBatch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'entity', 'entity_type', 'entity_id');
    }
}
