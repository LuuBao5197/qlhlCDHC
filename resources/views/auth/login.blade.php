<!DOCTYPE html>
<html lang="vi">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Đăng nhập</title>
    <style>
      @foreach ($resourceCoreStyles as $resourceStyle)
        {!! $resourceInlineCss($resourceStyle) !!}
      @endforeach
    </style>
    <link rel="shortcut icon" href="{{ $resourceAsset('images/favicon.png') }}" />
    <style>
      /* Nền trang đăng nhập: phủ toàn màn hình, giữ tỉ lệ ảnh, nét, có lớp phủ tối để chữ/form nổi bật */
      .login-page-bg {
        position: relative;
        min-height: 100vh;
        overflow: hidden;
        @if($loginBackgroundUrl ?? null)
          background-image: url("{{ $loginBackgroundUrl }}");
        @else
          background-image: linear-gradient(135deg, #1f2a56 0%, #3a4a8f 50%, #1f2a56 100%);
        @endif
        background-size: cover;
        background-position: center center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        image-rendering: -webkit-optimize-contrast;
      }
      .login-page-bg::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(20, 24, 48, .72) 0%, rgba(20, 24, 48, .45) 55%, rgba(20, 24, 48, .72) 100%);
        z-index: 0;
      }
      .login-page-bg > .row {
        position: relative;
        z-index: 1;
      }
      .login-card {
        background: rgba(255, 255, 255, .97);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, .35);
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .35);
      }
      .login-card .card-title {
        color: #1f2a56;
        font-weight: 700;
      }
      .login-card label {
        color: #2f3b4c;
      }
      .login-card .form-check-label,
      .login-card .sign-up {
        color: #4b5567 !important;
      }
      @media (max-width: 991.98px) {
        .login-card {
          width: 92% !important;
        }
      }
    </style>
  </head>
  <body>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="row w-100 m-0">
          <div class="content-wrapper full-page-wrapper d-flex align-items-center auth login-page-bg">
            <div class="card col-lg-4 mx-auto login-card">
              <div class="card-body px-5 py-5">
                <h3 class="card-title text-left mb-3">Đăng nhập</h3>
                <form method="POST" action="{{ route('login.perform') }}">
                  @csrf

                  @if (
                    $errors->any()
                )
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                  <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control p_input" required>
                  </div>
                  <div class="form-group">
                    <label>Mật khẩu *</label>
                    <input type="password" name="password" class="form-control p_input" required>
                  </div>
                  <div class="form-group d-flex align-items-center justify-content-between">
                    <div class="form-check">
                      <label class="form-check-label">
                        <input type="checkbox" name="remember" class="form-check-input"> Ghi nhớ đăng nhập </label>
                    </div>
                    <a href="{{ route('password.request') }}" class="forgot-pass">Quên mật khẩu</a>
                  </div>
                  <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-block enter-btn">Đăng nhập</button>
                  </div>
                  <p class="sign-up text-center text-muted small mt-3">
                    Tài khoản do Admin tạo và cấp mật khẩu. Liên hệ Admin nếu bạn chưa có tài khoản.
                  </p>
                </form>
              </div>
            </div>
          </div>
          <!-- content-wrapper ends -->
        </div>
        <!-- row ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <script>
      @foreach ($resourceCoreScripts as $resourceScript)
        {!! $resourceInlineJs($resourceScript) !!}
      @endforeach
    </script>
  </body>
</html>
