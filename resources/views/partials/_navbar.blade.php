<nav class="navbar p-0 fixed-top d-flex flex-row">
  <div class="navbar-brand-wrapper d-flex d-lg-none align-items-center justify-content-center">
    <a class="navbar-brand brand-logo-mini" href="index.html"><img src="{{ $resourceAsset('images/logo-mini.svg') }}" alt="logo" /></a>
  </div>
  <div class="navbar-menu-wrapper flex-grow d-flex align-items-stretch">
    <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
      <span class="mdi mdi-menu"></span>
    </button>
    <ul class="navbar-nav navbar-nav-right">
      <li class="nav-item dropdown border-left">
        <a class="nav-link count-indicator dropdown-toggle" id="messageDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
          <i class="mdi mdi-email"></i>
          <span class="count bg-success"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="messageDropdown">
          <h6 class="p-3 mb-0">Messages</h6>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item preview-item">
            <div class="preview-thumbnail">
              <img src="{{ $resourceAsset('images/faces/face4.jpg') }}" alt="image" class="rounded-circle profile-pic">
            </div>
            <div class="preview-item-content">
              <p class="preview-subject ellipsis mb-1">Mark send you a message</p>
              <p class="text-muted mb-0"> 1 Minutes ago </p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item preview-item">
            <div class="preview-thumbnail">
              <img src="{{ $resourceAsset('images/faces/face2.jpg') }}" alt="image" class="rounded-circle profile-pic">
            </div>
            <div class="preview-item-content">
              <p class="preview-subject ellipsis mb-1">Cregh send you a message</p>
              <p class="text-muted mb-0"> 15 Minutes ago </p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item preview-item">
            <div class="preview-thumbnail">
              <img src="{{ $resourceAsset('images/faces/face3.jpg') }}" alt="image" class="rounded-circle profile-pic">
            </div>
            <div class="preview-item-content">
              <p class="preview-subject ellipsis mb-1">Profile picture updated</p>
              <p class="text-muted mb-0"> 18 Minutes ago </p>
            </div>
          </a>
          <div class="dropdown-divider"></div>
          <p class="p-3 mb-0 text-center">4 new messages</p>
        </div>
      </li>
      <li class="nav-item dropdown border-left">
        <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
          <i class="mdi mdi-bell"></i>
          @if (($dashboardUnreadNotificationCount ?? 0) > 0)
            <span class="count bg-danger">{{ $dashboardUnreadNotificationCount }}</span>
@endif
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="notificationDropdown">
          <h6 class="p-3 mb-0">Thong bao</h6>
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
                    <span class="badge badge-danger ml-2">Moi</span>
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
              Chua co thong bao nao.
            </div>
            <div class="dropdown-divider"></div>
          @endforelse

          @php($allNotificationsUrl = \Illuminate\Support\Facades\Route::has('notifications.index') ? route('notifications.index') : url('/'))
          <a class="dropdown-item preview-item justify-content-center" href="{{ $allNotificationsUrl }}">
            <span class="preview-subject text-center font-weight-semibold">Xem tat ca thong bao</span>
          </a>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link" id="profileDropdown" href="#" data-toggle="dropdown">
          <div class="navbar-profile">
            <img class="img-xs rounded-circle" src="{{ $resourceAsset('images/faces/face15.jpg') }}" alt="">
            <p class="mb-0 d-none d-sm-block navbar-profile-name">{{ auth()->user()->name ?? '' }}</p>
            <i class="mdi mdi-menu-down d-none d-sm-block"></i>
          </div>
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list" aria-labelledby="profileDropdown">
          <h6 class="p-3 mb-0">Profile</h6>
          <div class="dropdown-divider"></div>
          {{-- <a class="dropdown-item preview-item">
            <div class="preview-thumbnail">
              <div class="preview-icon bg-dark rounded-circle">
                <i class="mdi mdi-settings text-success"></i>
              </div>
            </div>
            <div class="preview-item-content">
              <p class="preview-subject mb-1">Settings</p>
            </div>
          </a> --}}
          <div class="dropdown-divider"></div>
          @if(auth()?->user()?->isAdmin())
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
          <div class="dropdown-divider"></div>
          <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="dropdown-item preview-item btn btn-link p-0 text-left" style="color: #000;">
              <div class="preview-thumbnail">
                <div class="preview-icon bg-dark rounded-circle">
                  <i class="mdi mdi-logout text-danger"></i>
                </div>
              </div>
              <div class="preview-item-content">
                <p class="preview-subject mb-1">Log out</p>
              </div>
            </button>
          </form>
          <div class="dropdown-divider"></div>
          <p class="p-3 mb-0 text-center">Advanced settings</p>
        </div>
      </li>
    </ul>
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
      <span class="mdi mdi-format-line-spacing"></span>
    </button>
  </div>
</nav>
