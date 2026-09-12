@php
  // Menu hiển thị theo ROLE ĐANG ACTIVE (chỉ ảnh hưởng giao diện) — không phải kiểm tra quyền
  // thao tác thật sự (đã nằm trong Policy/FormRequest, dựa trên toàn bộ role user đang giữ).
  $activeRoleSlug = ($activeRole ?? null)?->slug;
@endphp
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

    @if(in_array($activeRoleSlug, ['training_office', 'training_office_head', 'admin', 'department_staff', 'department_head', 'leadership'], true))
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('schedule.semester') }}">
          <span class="menu-icon"><i class="mdi mdi-calendar-month"></i></span>
          <span class="menu-title">Lịch tổng quát học kỳ</span>
        </a>
      </li>
    @endif

    @if(in_array($activeRoleSlug, ['training_office', 'training_office_head', 'admin', 'leadership', 'department_staff', 'department_head', 'teacher'], true))
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('reports.teaching.overview') }}">
          <span class="menu-icon"><i class="mdi mdi-chart-bar"></i></span>
          <span class="menu-title">Báo cáo thống kê</span>
        </a>
      </li>
    @endif

    @if($activeRoleSlug === 'teacher')
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('teacher-slot-evaluations.index') }}">
          <span class="menu-icon"><i class="mdi mdi-clipboard-check"></i></span>
          <span class="menu-title">Đánh giá tiết học</span>
        </a>
      </li>
    @endif

    @if(in_array($activeRoleSlug, ['department_staff', 'department_head'], true))
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('monthly-schedule.directory') }}">
          <span class="menu-icon"><i class="mdi mdi-chalkboard-teacher"></i></span>
          <span class="menu-title">Phân công giảng dạy theo tháng</span>
        </a>
      </li>
      <li class="nav-item menu-items">
        <a class="nav-link" href="{{ route('teaching-support-requests.inbox') }}">
          <span class="menu-icon"><i class="mdi mdi-inbox"></i></span>
          <span class="menu-title">Inbox hỗ trợ liên khoa</span>
        </a>
      </li>
    @endif

    @if(in_array($activeRoleSlug, ['training_office', 'training_office_head', 'admin'], true))
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
        <a class="nav-link" href="{{ route('monthly-schedule.directory') }}">
          <span class="menu-icon"><i class="mdi mdi-chalkboard-teacher"></i></span>
          <span class="menu-title">Phân công giảng dạy theo tháng</span>
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

    @if(in_array($activeRoleSlug, ['admin', 'leadership'], true))
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

    @if($activeRoleSlug === 'admin')
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
