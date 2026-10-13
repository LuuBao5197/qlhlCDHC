<?php

namespace Modules\Training\Application\Management\TrainingClasses;

use App\Models\Role;
use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Application\Management\Shared\TrainingBatchPlanLockChecker;
use Modules\Training\Models\TrainingClass;

class ManageTrainingClassesHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return TrainingClass::class;
    }

    protected function relationships(): array
    {
        return ['students', 'trainingBatch', 'defaultRoom', 'accountUser'];
    }

    /**
     * Tao lop kem tai khoan xem lich lop (neu nhap account_email).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules($request));
        $email = $validated['account_email'] ?? null;
        unset($validated['account_email']);

        $class = DB::transaction(function () use ($validated, $email, $request): TrainingClass {
            $class = TrainingClass::create($validated);
            if ($email !== null) {
                $this->createAccount($class, $email, $request->user());
            }

            return $class;
        });

        return response()->json($this->freshModel($class), 201);
    }

    /**
     * Cap nhat lop. account_email: lop chua co tai khoan thi tao moi, da co thi doi email dang nhap;
     * de trong thi giu nguyen tai khoan hien co.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var TrainingClass $class */
        $class = $this->findModel($id);
        $validated = $request->validate($this->rules($request, $id));
        $email = $validated['account_email'] ?? null;
        unset($validated['account_email']);

        DB::transaction(function () use ($class, $validated, $email, $request): void {
            $class->update($validated);

            $account = $class->accountUser;
            if ($account !== null) {
                $updates = ['name' => $this->accountName($class)];
                if ($email !== null) {
                    $updates['email'] = $email;
                }
                $account->update($updates);
            } elseif ($email !== null) {
                $this->createAccount($class, $email, $request->user());
            }
        });

        return response()->json($this->freshModel($class));
    }

    public function destroy(int $id): JsonResponse
    {
        /** @var TrainingClass $class */
        $class = $this->findModel($id);
        $account = $class->accountUser;

        DB::transaction(function () use ($class, $account): void {
            $class->delete();
            // Tai khoan lop khong con y nghia khi lop bi xoa.
            $account?->delete();
        });

        return response()->json(['message' => 'Deleted successfully.']);
    }

    /**
     * Tai khoan role class_account: mat khau mac dinh cua he thong, bat buoc doi mat khau o lan dang nhap dau.
     */
    private function createAccount(TrainingClass $class, string $email, ?User $actor): void
    {
        $user = User::create([
            'name' => $this->accountName($class),
            'email' => $email,
            'password' => Hash::make((string) config('accounts.default_password')),
            'must_change_password' => true,
            'role' => null,
            'position' => null,
            'status' => User::STATUS_APPROVED,
            'email_verified_at' => now(),
        ]);

        $user->roles()->attach(Role::query()->where('slug', Role::CLASS_ACCOUNT)->pluck('id'));
        $class->forceFill(['account_user_id' => $user->id])->save();
        $class->setRelation('accountUser', $user);

        if ($actor !== null) {
            app(InternalNotificationService::class)->notifyAccountCreated($user, $actor);
        }
    }

    private function accountName(TrainingClass $class): string
    {
        return 'Tài khoản lớp ' . ($class->code ?: $class->name);
    }

    protected function searchColumns(): array
    {
        return ['code', 'name', 'status'];
    }

    protected function filterableColumns(): array
    {
        return ['status', 'training_batch_id'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        $accountUserId = $id !== null ? TrainingClass::query()->whereKey($id)->value('account_user_id') : null;

        return [
            'account_email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($accountUserId)],
            'code' => ['required', 'string', 'max:255', Rule::unique('classes', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255', Rule::unique('classes', 'name')->ignore($id)],
            'training_batch_id' => [
                'required', 'integer', 'exists:training_batches,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($id): void {
                    $this->guardTrainingBatchChange($id, (int) $value, $fail);
                },
            ],
            'course_year' => ['nullable', 'integer', 'min:2000', 'max:' . ((int) date('Y') + 10)],
            'total_students' => ['required', 'integer', 'min:0'],
            'default_room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive', 'archived'])],
        ];
    }

    /**
     * A training_batch with a submitted/approved semester plan is locked for class
     * membership changes: adding a class to it, or moving a class out of it, would
     * leave that plan's class list out of sync with what was actually reviewed.
     */
    private function guardTrainingBatchChange(?int $classId, int $newBatchId, \Closure $fail): void
    {
        $checker = new TrainingBatchPlanLockChecker();
        $currentBatchId = $classId !== null
            ? TrainingClass::query()->find($classId)?->training_batch_id
            : null;
        $currentBatchId = $currentBatchId !== null ? (int) $currentBatchId : null;

        if ($currentBatchId === $newBatchId) {
            return;
        }

        if ($currentBatchId !== null && $checker->isLocked($currentBatchId)) {
            $fail('Lớp này đang thuộc một khóa đào tạo đã có kế hoạch học kỳ đang chờ duyệt hoặc đã duyệt, không thể đổi sang khóa khác.');

            return;
        }

        if ($checker->isLocked($newBatchId)) {
            $fail('Khóa đào tạo đã chọn đã có kế hoạch học kỳ đang chờ duyệt hoặc đã duyệt, không thể thêm lớp mới vào khóa này.');
        }
    }
}
