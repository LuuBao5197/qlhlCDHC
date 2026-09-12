<?php

namespace App\Models;

use App\Enums\Position;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const ADMIN = 'admin';
    public const STUDENT = 'student';
    public const TEACHER = 'teacher';
    public const DEPARTMENT_STAFF = 'department_staff';
    public const DEPARTMENT_HEAD = 'department_head';
    public const TRAINING_OFFICE = 'training_office';
    public const TRAINING_OFFICE_HEAD = 'training_office_head';
    public const LEADERSHIP = 'leadership';

    protected $fillable = [
        'slug',
        'name',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Suy ra role slug mới từ tổ hợp (role, position) cũ — dùng để backfill dữ liệu
     * và giữ tương thích cho factory/seeder vẫn set 'role'/'position' theo kiểu cũ.
     *
     * @return list<string>
     */
    public static function slugsForLegacy(?string $role, string|Position|null $position): array
    {
        $positionValue = $position instanceof Position ? $position->value : $position;

        return match ($role) {
            self::ADMIN => [self::ADMIN],
            self::STUDENT => [self::STUDENT],
            self::TEACHER => [self::TEACHER],
            self::DEPARTMENT_STAFF => $positionValue === 'department_head'
                ? [self::DEPARTMENT_STAFF, self::DEPARTMENT_HEAD]
                : [self::DEPARTMENT_STAFF],
            self::TRAINING_OFFICE => in_array($positionValue, ['training_head', 'training_deputy_head'], true)
                ? [self::TRAINING_OFFICE, self::TRAINING_OFFICE_HEAD]
                : [self::TRAINING_OFFICE],
            self::LEADERSHIP => [self::LEADERSHIP],
            default => [],
        };
    }
}
