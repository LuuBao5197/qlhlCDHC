<?php

namespace App\Application\Account\UpdateProfile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Đặt tên bag riêng vì trang settings hiển thị đồng thời form đổi mật khẩu (xem ChangePasswordRequest)
     * — tránh lỗi validate của form này lẫn sang form kia khi cả hai cùng redirect back().
     */
    protected $errorBag = 'updateProfile';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Cố ý KHÔNG khai báo 'email' hay 'employee_code' ở đây: đây là 2 trường định danh
     * dùng để liên kết User <-> Teacher với dữ liệu do Admin quản lý (Modules/Training).
     * Vì Handler chỉ dùng $request->validated(), dữ liệu 2 trường này (nếu client cố tình
     * gửi lên) sẽ luôn bị loại bỏ và không bao giờ được ghi xuống DB qua slice này.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên.',
            'name.max' => 'Họ tên không được dài quá 255 ký tự.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'avatar.image' => 'Ảnh đại diện phải là file ảnh.',
            'avatar.max' => 'Ảnh đại diện không được lớn hơn 2MB.',
        ];
    }
}
