<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Quên mật khẩu</title>
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
                <h3 class="card-title text-left mb-3">Quên mật khẩu</h3>
                <p class="text-muted">Nhập email tài khoản của bạn, chúng tôi sẽ gửi liên kết đặt lại mật khẩu.</p>

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

                <form method="POST" action="{{ route('password.email') }}">
                  @csrf
                  <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control p_input" required autofocus>
                  </div>
                  <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-block enter-btn">Gửi liên kết đặt lại mật khẩu</button>
                  </div>
                  <p class="sign-up text-center mt-3"><a href="{{ route('login') }}">Quay lại đăng nhập</a></p>
                </form>
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
