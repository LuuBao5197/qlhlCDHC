<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yêu cầu quên mật khẩu do người dùng gửi khi hệ thống không gửi được email —
 * Admin xem và duyệt/từ chối thủ công trong màn Quản trị. Khi duyệt, token của
 * request cho phép người dùng truy cập trang đặt mật khẩu mới (xem ForgotPassword
 * và ResetPassword trong App\Application\Auth).
 */
class PasswordResetRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'token',
        'status',
        'requested_at',
        'decided_at',
        'decided_by',
        'used_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'decided_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }
}
