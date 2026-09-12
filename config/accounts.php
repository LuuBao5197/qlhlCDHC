<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Account Password
    |--------------------------------------------------------------------------
    |
    | Hệ thống chạy trong mạng nội bộ, không gửi được email — Admin tạo tài
    | khoản và cấp trực tiếp mật khẩu mặc định này cho người dùng. Tài khoản
    | bắt buộc đổi mật khẩu ngay lần đăng nhập đầu tiên (xem `must_change_password`
    | trên bảng users và middleware EnsureMustChangePassword).
    |
    */

    'default_password' => env('DEFAULT_ACCOUNT_PASSWORD', 'Cdhc@123'),

];
