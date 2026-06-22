<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
    <a class="sidebar-brand brand-logo" href="{{ url('/home') }}"><span class="text-white font-weight-bold">QLHL</span></a>
    <a class="sidebar-brand brand-logo-mini" href="{{ url('/home') }}"><span class="text-white">Q</span></a>
  </div>

  <ul class="nav">
    <li class="nav-item nav-category">
      <span class="nav-link">Navigation</span>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ url('/home') }}">
        <span class="menu-icon"><i class="mdi mdi-view-dashboard"></i></span>
        <span class="menu-title">Dashboard</span>
      </a>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('schedule.index') }}">
        <span class="menu-icon"><i class="mdi mdi-calendar-clock"></i></span>
        <span class="menu-title">Lich huan luyen</span>
      </a>
    </li>

    @if(auth()->check() && (auth()->user()->isTrainingOffice() || auth()->user()->isAdmin()))
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('department-monthly-assignment-batches.index') }}">
          <span class="menu-icon"><i class="mdi mdi-clipboard-check-outline"></i></span>
          <span class="menu-title">Phê duyệt phân công</span>
        </a>
      </li>
    @endif

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('management.index') }}">
        <span class="menu-icon"><i class="mdi mdi-database"></i></span>
        <span class="menu-title">Quan tri danh muc</span>
      </a>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('settings') }}">
        <span class="menu-icon"><i class="mdi mdi-settings"></i></span>
        <span class="menu-title">Settings</span>
      </a>
    </li>

    @if(auth()->check() && auth()->user()->isAdmin())
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('admin.index') }}">
          <span class="menu-icon"><i class="mdi mdi-shield-account"></i></span>
          <span class="menu-title">Admin</span>
        </a>
      </li>
    @endif
  </ul>
</nav>
