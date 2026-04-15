<?php

namespace Modules\Training\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalAction extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\ApprovalActionFactory> */
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'step_code',
        'action',
        'acted_by',
        'acted_at',
        'comment',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
