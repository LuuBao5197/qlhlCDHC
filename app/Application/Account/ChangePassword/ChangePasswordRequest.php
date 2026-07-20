<?php

namespace App\Application\Account\ChangePassword;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Đặt tên bag riêng vì trang settings hiển thị đồng thời form cập nhật hồ sơ (xem UpdateProfileRequest)
     * — tránh lỗi validate của form này lẫn sang form kia khi cả hai cùng redirect back().
     */
    protected $errorBag = 'changePassword';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
            'password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if (
                $user !== null
                && $this->filled('current_password')
                && ! Hash::check((string) $this->input('current_password'), $user->password)
            ) {
                $validator->errors()->add('current_password', 'Mật khẩu hiện tại không đúng.');
            }
        });
    }
}
