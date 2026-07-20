<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Đặt lại mật khẩu</title>
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
                <h3 class="card-title text-left mb-3">Đặt lại mật khẩu</h3>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                  @csrf
                  <input type="hidden" name="token" value="{{ old('token', $token) }}">

                  <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" class="form-control p_input" required>
                  </div>
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
                    <button type="submit" class="btn btn-primary btn-block enter-btn">Đặt lại mật khẩu</button>
                  </div>
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
