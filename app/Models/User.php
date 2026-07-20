<?php

namespace App\Models;

use App\Enums\Position;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Training\Models\Department;
use Modules\Training\Models\Teacher;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_LEADERSHIP = 'leadership';
    public const ROLE_TRAINING_OFFICE = 'training_office';
    public const ROLE_DEPARTMENT_STAFF = 'department_staff';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_STUDENT = 'student';
    public const ROLE_ADMIN = 'admin';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_LOCKED = 'locked';

    protected $attributes = [
        'role' => self::ROLE_STUDENT,
        'status' => self::STATUS_PENDING,
    ];

    public static array $availableRoles = [
        self::ROLE_LEADERSHIP,
        self::ROLE_TRAINING_OFFICE,
        self::ROLE_DEPARTMENT_STAFF,
        self::ROLE_TEACHER,
        self::ROLE_STUDENT,
        self::ROLE_ADMIN,
    ];

    public static array $availableStatuses = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_LOCKED,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'requested_role',
        'position',
        'status',
        'department_id',
        'requested_department_id',
        'employee_code',
        'phone',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'position' => Position::class,
    ];

    public static function getAvailableRoles(): array
    {
        return self::$availableRoles;
    }

    public static function getAvailableStatuses(): array
    {
        return self::$availableStatuses;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requestedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'requested_department_id');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isLeadership(): bool
    {
        return $this->isRole(self::ROLE_LEADERSHIP);
    }

    public function isTrainingOffice(): bool
    {
        return $this->isRole(self::ROLE_TRAINING_OFFICE);
    }

    public function isDepartmentStaff(): bool
    {
        return $this->isRole(self::ROLE_DEPARTMENT_STAFF);
    }

    public function isTeacher(): bool
    {
        return $this->isRole(self::ROLE_TEACHER);
    }

    public function isStudent(): bool
    {
        return $this->isRole(self::ROLE_STUDENT);
    }

    public function isAdmin(): bool
    {
        return $this->isRole(self::ROLE_ADMIN);
    }

    public function isStatus(string $status): bool
    {
        return $this->status === $status;
    }

    public function isApproved(): bool
    {
        return $this->isStatus(self::STATUS_APPROVED);
    }

    public function isPending(): bool
    {
        return $this->isStatus(self::STATUS_PENDING);
    }

    public function isRejected(): bool
    {
        return $this->isStatus(self::STATUS_REJECTED);
    }

    public function isLocked(): bool
    {
        return $this->isStatus(self::STATUS_LOCKED);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\PasswordResetNotification($token));
    }
}
