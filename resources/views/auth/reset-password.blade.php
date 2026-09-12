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

                @if ($resetRequest === null)
                    <div class="alert alert-danger">Liên kết đặt lại mật khẩu không hợp lệ.</div>
                    <p class="text-center"><a href="{{ route('password.request') }}">Gửi yêu cầu mới</a></p>
                @elseif ($resetRequest->isUsed())
                    <div class="alert alert-secondary">Liên kết này đã được sử dụng.</div>
                    <p class="text-center"><a href="{{ route('password.request') }}">Gửi yêu cầu mới</a></p>
                @elseif ($resetRequest->isRejected())
                    <div class="alert alert-danger">Yêu cầu đặt lại mật khẩu của bạn đã bị Admin từ chối.</div>
                    <p class="text-center"><a href="{{ route('password.request') }}">Gửi yêu cầu mới</a></p>
                @elseif ($resetRequest->isPending())
                    <div class="alert alert-warning">
                        Yêu cầu của bạn đang chờ Admin duyệt. Vui lòng lưu lại địa chỉ trang này và quay lại kiểm tra sau.
                    </div>
                    <p class="text-center"><a href="{{ url()->current() }}">Kiểm tra lại</a> · <a href="{{ route('login') }}">Quay lại đăng nhập</a></p>
                @else
                    <p class="text-muted">Yêu cầu của bạn đã được duyệt. Vui lòng đặt mật khẩu mới.</p>
                    <form method="POST" action="{{ route('password.update') }}">
                      @csrf
                      <input type="hidden" name="token" value="{{ old('token', $token) }}">

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
