<?php

namespace Modules\Schedule\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Training\Models\ApprovalRequest;

class MonthlyAssignmentDossier extends Model
{
    protected $table = 'monthly_assignment_dossiers';

    protected $fillable = [
        'month',
        'year',
        'status',
        'current_step',
        'created_by',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'completed_at',
        'version',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RETURNED = 'returned';

    public const STEP_DRAFT = 'draft';
    public const STEP_TRAINING_OFFICE_REVIEW = 'training_office_review';
    public const STEP_LEADERSHIP_REVIEW = 'leadership_review';
    public const STEP_COMPLETED = 'completed';

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(
            DepartmentMonthlyAssignmentBatch::class,
            'monthly_assignment_dossier_batches',
            'dossier_id',
            'department_monthly_assignment_batch_id'
        )->withPivot('batch_version')->withTimestamps();
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'entity', 'entity_type', 'entity_id');
    }
}
