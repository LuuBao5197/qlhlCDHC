<?php

namespace App\Support;

use App\Models\AdminBackfillLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class AdminBackfillContext
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'admin_backfill' => ['nullable', 'boolean'],
            'admin_backfill_reason' => ['required_if:admin_backfill,1', 'nullable', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'admin_backfill_reason.required_if' => 'Vui long nhap ly do bo sung du lieu cu.',
            'admin_backfill_reason.min' => 'Ly do bo sung du lieu cu toi thieu 10 ky tu.',
            'admin_backfill_reason.max' => 'Ly do bo sung du lieu cu toi da 1000 ky tu.',
        ];
    }

    public static function isActive(FormRequest $request): bool
    {
        return $request->user()?->isAdmin() === true && $request->boolean('admin_backfill');
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function log(FormRequest $request, string $feature, ?Model $loggable = null, array $meta = []): ?AdminBackfillLog
    {
        if (! self::isActive($request)) {
            return null;
        }

        return AdminBackfillLog::query()->create([
            'feature' => $feature,
            'loggable_type' => $loggable?->getMorphClass(),
            'loggable_id' => $loggable?->getKey(),
            'admin_id' => $request->user()->id,
            'reason' => (string) $request->input('admin_backfill_reason'),
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
