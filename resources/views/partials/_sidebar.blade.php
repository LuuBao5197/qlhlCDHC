<nav class="sidebar sidebar-offcanvas" id="sidebar">
  <div class="sidebar-brand-wrapper d-none d-lg-flex align-items-center justify-content-center fixed-top">
    <a class="sidebar-brand brand-logo" href="{{ url('/home') }}"><span class="text-white font-weight-bold">QLHL</span></a>
    <a class="sidebar-brand brand-logo-mini" href="{{ url('/home') }}"><span class="text-white">Q</span></a>
  </div>

  <ul class="nav">
    <li class="nav-item nav-category">
      <span class="nav-link">Điều hướng</span>
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
        <span class="menu-title">Lịch huấn luyện</span>
      </a>
    </li>

    @if(auth()->check() && (auth()->user()->isTrainingOffice() || auth()->user()->isAdmin() || auth()->user()->isLeadership() || auth()->user()->isDepartmentStaff() || auth()->user()->isTeacher()))
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('reports.teaching.overview') }}">
          <span class="menu-icon"><i class="mdi mdi-chart-bar"></i></span>
          <span class="menu-title">Báo cáo thống kê</span>
        </a>
      </li>
    @endif

    @if(auth()->check() && auth()->user()->isTeacher())
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('teacher-slot-evaluations.index') }}">
          <span class="menu-icon"><i class="mdi mdi-clipboard-check"></i></span>
          <span class="menu-title">Đánh giá tiết học</span>
        </a>
      </li>
    @endif

    @if(auth()->check() && auth()->user()->isDepartmentStaff())
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('teaching-support-requests.inbox') }}">
          <span class="menu-icon"><i class="mdi mdi-inbox"></i></span>
          <span class="menu-title">Inbox hỗ trợ liên khoa</span>
        </a>
      </li>
    @endif

    @if(auth()->check() && (auth()->user()->isTrainingOffice() || auth()->user()->isAdmin()))
      <li class="nav-item nav-category">
        <span class="nav-link">Nghiệp vụ Phòng Đào tạo</span>
      </li>

      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('duty-log.index') }}">
          <span class="menu-icon"><i class="mdi mdi-clipboard-text"></i></span>
          <span class="menu-title">Nhật ký huấn luyện</span>
        </a>
      </li>
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('monthly-schedule.initialize.form') }}">
          <span class="menu-icon"><i class="mdi mdi-calendar-plus"></i></span>
          <span class="menu-title">Khởi tạo lịch tháng</span>
        </a>
      </li>
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('department-monthly-assignment-batches.index') }}">
          <span class="menu-icon"><i class="mdi mdi-clipboard-check-outline"></i></span>
          <span class="menu-title">Phê duyệt phân công</span>
        </a>
      </li>
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('teaching-support-requests.index') }}">
          <span class="menu-icon"><i class="mdi mdi-handshake-outline"></i></span>
          <span class="menu-title">Yêu cầu hỗ trợ liên khoa</span>
        </a>
      </li>
    @endif

    @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isLeadership()))
      <li class="nav-item nav-category">
        <span class="nav-link">Quản trị</span>
      </li>

      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('management.index') }}">
          <span class="menu-icon"><i class="mdi mdi-database"></i></span>
          <span class="menu-title">Quản trị danh mục</span>
        </a>
      </li>
    @endif

    @if(auth()->check() && auth()->user()->isAdmin())
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('admin.index') }}">
          <span class="menu-icon"><i class="mdi mdi-shield-account"></i></span>
          <span class="menu-title">Quản trị hệ thống</span>
        </a>
      </li>
    @endif

    <li class="nav-item nav-category">
      <span class="nav-link">Tài khoản</span>
    </li>

    <li class="nav-item menu-items">
      <a class="nav-link" href="{{ route('settings') }}">
        <span class="menu-icon"><i class="mdi mdi-settings"></i></span>
        <span class="menu-title">Cài đặt</span>
      </a>
    </li>
  </ul>
</nav>
