<?php

namespace Modules\Training\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends Model
{
    /** @use HasFactory<\Modules\Training\Database\Factories\ApprovalRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'submitted_by',
        'current_step',
        'status',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class);
    }
}
