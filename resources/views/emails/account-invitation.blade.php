@component('mail::message')
# Kích hoạt tài khoản{{ $roleTitle !== null ? ' ' . $roleTitle : '' }}

Xin chào **{{ $userName }}**,

Admin vừa tạo tài khoản{{ $roleTitle !== null ? ' ' . $roleTitle : '' }} cho bạn trên hệ thống {{ config('app.name') }}. Vui lòng bấm nút bên dưới để thiết lập mật khẩu đăng nhập lần đầu.

@component('mail::button', ['url' => $activationUrl])
Thiết lập mật khẩu
@endcomponent

Liên kết này chỉ có hiệu lực trong **{{ $expiresInMinutes }} phút**. Nếu liên kết hết hạn, vui lòng liên hệ Admin để được gửi lại email kích hoạt.

Nếu bạn không yêu cầu tài khoản này, vui lòng bỏ qua email này.

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent
