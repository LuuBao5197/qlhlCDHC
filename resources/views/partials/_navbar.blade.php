<nav class="navbar p-0 fixed-top d-flex flex-row">
  <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center">
    <a class="navbar-brand brand-logo-mini" href="{{ url('/home') }}"><img src="{{ $resourceAsset('images/logo-mini.svg') }}" alt="logo" /></a>
  </div>
  <div class="navbar-menu-wrapper flex-grow d-flex align-items-stretch">
    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
      <span class="mdi mdi-menu"></span>
    </button>
    <ul class="navbar-nav navbar-nav-right">
      <li class="nav-item dropdown border-left">
        <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
          <i class="mdi mdi-bell"></i>
          @if (($dashboardUnreadNotificationCount ?? 0) > 0)
            <span class="count bg-danger">{{ $dashboardUnreadNotificationCount }}</span>
@endif
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
          <h6 class="p-3 mb-0">Thông báo</h6>
          <div class="dropdown-divider"></div>

          @forelse(($dashboardNotifications ?? collect()) as $notification)
            @php($notificationUrl = \Illuminate\Support\Facades\Route::has('notifications.show') ? route('notifications.show', $notification['id']) : ($notification['url'] ?? url('/')))
            <a class="dropdown-item preview-item {{ $notification['is_read'] ? '' : 'bg-light' }}" href="{{ $notificationUrl }}">
              <div class="preview-thumbnail">
                <div class="preview-icon bg-dark rounded-circle">
                  <i class="mdi {{ $notification['icon'] }} {{ $notification['icon_color_class'] }}"></i>
                </div>
              </div>
              <div class="preview-item-content">
                <p class="preview-subject mb-1">
                  {{ $notification['title'] }}
                  @if (! $notification['is_read'])
                    <span class="badge badge-danger ml-2">Mới</span>
                  @endif
                </p>
                <p class="text-muted ellipsis mb-0">
                  {{ $notification['excerpt'] }}
                </p>
                <p class="text-muted mb-0 small">
                  <span class="badge {{ $notification['badge_class'] }}">{{ $notification['type_label'] }}</span>
                  <span class="ml-2">{{ $notification['created_at_human'] }}</span>
                </p>
              </div>
            </a>
            <div class="dropdown-divider"></div>
          @empty
            <div class="px-3 py-4 text-center text-muted">
              Chưa có thông báo nào.
            </div>
            <div class="dropdown-divider"></div>
          @endforelse

          @php($allNotificationsUrl = \Illuminate\Support\Facades\Route::has('notifications.index') ? route('notifications.index') : url('/'))
          <a class="dropdown-item preview-item justify-content-center" href="{{ $allNotificationsUrl }}">
            <span class="preview-subject text-center font-weight-semibold">Xem tất cả thông báo</span>
          </a>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link" id="profileDropdown" href="#" data-toggle="dropdown">
          <div class="navbar-profile">
            <img class="img-xs rounded-circle" src="{{ auth()->user() && auth()->user()->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->avatar_path) : $resourceAsset('images/faces/face15.jpg') }}" alt="">
            <p class="mb-0 d-none d-sm-block navbar-profile-name">{{ auth()->user()->name ?? '' }}</p>
            <i class="mdi mdi-menu-down d-none d-sm-block"></i>
          </div>
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="profileDropdown">
          <h6 class="p-3 mb-0">Tài khoản</h6>
          @if (($activeRole ?? null) !== null)
            <p class="px-3 mb-0 text-muted small">Đang xem với vai trò: <strong>{{ $activeRole->name }}</strong></p>
          @endif
          <div class="dropdown-divider"></div>
          @php($otherRoles = auth()->user()?->roles->reject(fn ($role) => ($activeRole ?? null) !== null && $role->id === $activeRole->id) ?? collect())
          @if ($otherRoles->isNotEmpty())
            <p class="px-3 mb-1 text-muted small">Chuyển sang vai trò khác:</p>
            @foreach ($otherRoles as $role)
              <form method="POST" action="{{ route('account.active-role.update') }}" class="m-0">
                @csrf
                <input type="hidden" name="role" value="{{ $role->slug }}">
                <button type="submit" class="dropdown-item preview-item btn btn-link p-0 text-left">
                  <div class="preview-thumbnail">
                    <div class="preview-icon bg-dark rounded-circle">
                      <i class="mdi mdi-account-switch text-info"></i>
                    </div>
                  </div>
                  <div class="preview-item-content">
                    <p class="preview-subject mb-1">{{ $role->name }}</p>
                  </div>
                </button>
              </form>
            @endforeach
            <div class="dropdown-divider"></div>
          @endif
          <a class="dropdown-item preview-item" href="{{ route('settings') }}">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-settings text-success"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject mb-1">Cài đặt</p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          @if(($activeRole ?? null)?->slug === 'admin')
            <a class="dropdown-item preview-item" href="{{ route('admin.index') }}">
              <div class="preview-thumbnail">
                <div class="preview-icon bg-dark rounded-circle">
                  <i class="mdi mdi-shield-account text-warning"></i>
                </div>
              </div>
              <div class="preview-item-content">
                <p class="preview-subject mb-1">Admin Panel</p>
              </div>
            </a>
            <div class="dropdown-divider"></div>
          @endif
          <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="dropdown-item preview-item btn btn-link p-0 text-left">
              <div class="preview-thumbnail">
                <div class="preview-icon bg-dark rounded-circle">
                  <i class="mdi mdi-logout text-danger"></i>
                </div>
              </div>
              <div class="preview-item-content">
                <p class="preview-subject mb-1">Đăng xuất</p>
              </div>
            </button>
          </form>
        </div>
      </li>
    </ul>
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
      <span class="mdi mdi-format-line-spacing"></span>
    </button>
  </div>
</nav>
