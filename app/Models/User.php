<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Traits\ToStringFormat;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    public const ROLE_LEADERSHIP = 'leadership';
    public const ROLE_TRAINING_OFFICE = 'training_office';
    public const ROLE_DEPARTMENT_STAFF = 'department_staff';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_STUDENT = 'student';
    public const ROLE_ADMIN = 'admin';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Default values for new users.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'role' => self::ROLE_STUDENT,
        'status' => self::STATUS_PENDING,
    ];

    /**
     * Available roles in the system.
     *
     * @var array<int, string>
     */
    public static array $availableRoles = [
        self::ROLE_LEADERSHIP,
        self::ROLE_TRAINING_OFFICE,
        self::ROLE_DEPARTMENT_STAFF,
        self::ROLE_TEACHER,
        self::ROLE_STUDENT,
        self::ROLE_ADMIN,
    ];

    /** @var array<int, string> */
    public static array $availableStatuses = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public static function getAvailableRoles(): array
    {
        return self::$availableRoles;
    }

    public static function getAvailableStatuses(): array
    {
        return self::$availableStatuses;
    }

    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isLeadership(): bool
    {
        return $this->isRole(self::ROLE_LEADERSHIP + '');
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
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
