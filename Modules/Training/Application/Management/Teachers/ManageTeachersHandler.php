<?php

namespace Modules\Training\Application\Management\Teachers;

use App\Models\User;
use App\Services\AccountInvitationService;
use App\Services\InternalNotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Training\Application\Management\Shared\CrudHandler;
use Modules\Training\Models\Teacher;

class ManageTeachersHandler extends CrudHandler
{
    protected function modelClass(): string
    {
        return Teacher::class;
    }

    protected function relationships(): array
    {
        return ['department'];
    }

    protected function searchColumns(): array
    {
        return ['teacher_code', 'name', 'status'];
    }

    protected function rules(Request $request, ?int $id = null): array
    {
        $rules = [
            'teacher_code' => ['required', 'string', 'max:255', Rule::unique('teachers', 'teacher_code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];

        // Email chỉ bắt buộc khi tạo mới: đây là địa chỉ dùng để gửi mail kích hoạt tài khoản.
        // Không cho sửa qua đây khi update để tránh việc email của User bị đổi ngầm ngoài ý muốn.
        if ($id === null) {
            $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')];
        }

        return $rules;
    }

    /**
     * Tạo đồng thời User (tài khoản đăng nhập) + Teacher (hồ sơ giảng viên) liên kết qua employee_code/teacher_code,
     * sau đó gửi email mời kích hoạt tài khoản (đặt mật khẩu lần đầu) qua token password_reset_tokens chuẩn của Laravel.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules($request));
        $actor = $request->user();

        try {
            $teacher = DB::transaction(function () use ($validated): Teacher {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make(Str::random(40)),
                    'role' => User::ROLE_TEACHER,
                    'status' => User::STATUS_APPROVED,
                    'employee_code' => $validated['teacher_code'],
                    'department_id' => $validated['department_id'] ?? null,
                ]);

                return Teacher::create([
                    'teacher_code' => $validated['teacher_code'],
                    'name' => $validated['name'],
                    'status' => $validated['status'],
                    'department_id' => $validated['department_id'] ?? null,
                    'user_id' => $user->id,
                ]);
            });
        } catch (QueryException $e) {
            // Race condition hiếm gặp: 2 request cùng tạo trùng teacher_code/email vượt qua validate
            // cùng lúc, unique constraint ở DB chặn lại — trả về lỗi validate thân thiện thay vì 500.
            throw ValidationException::withMessages([
                'teacher_code' => ['Không thể tạo tài khoản giáo viên do dữ liệu bị trùng (email hoặc mã giáo viên). Vui lòng thử lại.'],
            ]);
        }

        // Tài khoản đã tạo thành công dù email mời có gửi được hay không: service tự
        // ghi log khi gửi lỗi (SMTP timeout, sai cấu hình...) và Admin có thể gửi lại sau.
        app(AccountInvitationService::class)->invite($teacher->user);

        if ($actor !== null) {
            app(InternalNotificationService::class)->notifyAccountCreated($teacher->user, $actor);
        }

        return response()->json($this->freshModel($teacher), 201);
    }
}