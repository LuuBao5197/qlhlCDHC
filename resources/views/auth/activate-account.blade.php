<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Kích hoạt tài khoản</title>
    <style>
      @foreach ($resourceCoreStyles as $resourceStyle)
        {!! $resourceInlineCss($resourceStyle) !!}
      @endforeach
    </style>
    <link rel="shortcut icon" href="{{ $resourceAsset('images/favicon.png') }}" />
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="row w-100 m-0">
          <div class="content-wrapper full-page-wrapper d-flex align-items-center auth login-bg">
            <div class="card col-lg-4 mx-auto">
              <div class="card-body px-5 py-5">
                <h3 class="card-title text-left mb-3">Kích hoạt tài khoản</h3>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! $isValid)
                  <div class="alert alert-warning">
                    Liên kết kích hoạt không hợp lệ hoặc đã hết hạn. Vui lòng liên hệ Admin để được gửi lại email kích hoạt.
                  </div>
                  <p class="text-center"><a href="{{ route('login') }}">Quay lại trang đăng nhập</a></p>
                @else
                  <p class="text-muted">Thiết lập mật khẩu đăng nhập lần đầu cho tài khoản <strong>{{ $email }}</strong>.</p>

                  <form method="POST" action="{{ route('account.activate.submit') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="form-group">
                      <label>Mật khẩu mới</label>
                      <input type="password" name="password" class="form-control p_input" minlength="8" required autofocus>
                      <small class="text-muted">Tối thiểu 8 ký tự.</small>
                    </div>
                    <div class="form-group">
                      <label>Xác nhận mật khẩu</label>
                      <input type="password" name="password_confirmation" class="form-control p_input" minlength="8" required>
                    </div>
                    <div class="text-center">
                      <button type="submit" class="btn btn-primary btn-block enter-btn">Kích hoạt tài khoản</button>
                    </div>
                  </form>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script>
      @foreach ($resourceCoreScripts as $resourceScript)
        {!! $resourceInlineJs($resourceScript) !!}
      @endforeach
    </script>
  </body>
</html>
