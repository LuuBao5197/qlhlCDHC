<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @php
        $dashboardStyles = $resourceDashboardStyles;
        $dashboardScripts = $resourceDashboardScripts;
    @endphp
    <style>
        @foreach ($dashboardStyles as $dashboardStyle)
            {!! $resourceInlineCss($dashboardStyle) !!}
        @endforeach
    </style>
    <style>
        :root {
            --qlhl-bg: #F4F6F3;
            --qlhl-surface: #ffffff;
            --qlhl-border: #D9E0D3;
            --qlhl-text: #2F3B2C;
            --qlhl-text-muted: #6B7A66;
            --qlhl-sidebar-bg: #3B533E;
            --qlhl-sidebar-bg-active: #2E4030;
            --qlhl-sidebar-text: #E7ECE4;
            --qlhl-sidebar-text-active: #ffffff;
            --qlhl-primary: #5A7255;
            --qlhl-primary-dark: #4B5E43;
            --qlhl-radius: 0.5rem;
        }

        body,
        .navbar-nav-right .nav-link,
        .sidebar .nav .nav-item .nav-link {
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            background: var(--qlhl-bg);
            color: var(--qlhl-text);
            font-size: 0.9rem;
        }

        /* Sidebar */
        .sidebar,
        .sidebar .sidebar-brand-wrapper {
            background: var(--qlhl-sidebar-bg) !important;
        }

        @media (min-width: 992px) {
            .sidebar {
                position: fixed;
                top: 70px;
                left: 0;
                bottom: 0;
                overflow-y: auto;
            }

            /* Guards against a 1px seam between the fixed sidebar and navbar
           caused by sub-pixel rounding under fractional display scaling. */
            .sidebar,
            .sidebar .sidebar-brand-wrapper {
                box-shadow: 1px 0 0 0 var(--qlhl-sidebar-bg);
            }

            .page-body-wrapper {
                margin-left: 244px;
            }
        }

        .sidebar .nav .nav-item .nav-link {
            color: var(--qlhl-sidebar-text);
            border-radius: var(--qlhl-radius);
            margin: 0.125rem 0.75rem;
            padding: 0.65rem 1rem;
        }

        .sidebar .nav .nav-item.nav-category .nav-link {
            color: var(--qlhl-sidebar-text);
            opacity: .6;
            text-transform: uppercase;
            font-size: .72rem;
            letter-spacing: .06em;
            font-weight: 600;
            margin: 0.75rem 1rem 0.25rem;
            padding: 0;
        }

        .sidebar .nav .nav-item .nav-link:hover,
        .sidebar .nav .nav-item.active>.nav-link {
            background: var(--qlhl-sidebar-bg-active);
            color: var(--qlhl-sidebar-text-active);
        }

        .sidebar .nav .nav-item .nav-link .menu-icon {
            color: inherit;
        }

        /* Navbar */
        .navbar {
            background: var(--qlhl-sidebar-bg) !important;
            border-bottom: 1px solid var(--qlhl-sidebar-bg-active);
        }

        .navbar .navbar-nav-right .nav-link,
        .navbar .navbar-toggler .mdi,
        .navbar .navbar-profile-name {
            color: var(--qlhl-sidebar-text) !important;
        }

        .navbar .navbar-menu-wrapper .navbar-nav .nav-item .nav-link {
            color: var(--qlhl-sidebar-text) !important;
        }

        .navbar .nav-item.dropdown.border-left {
            border-left-color: var(--qlhl-sidebar-bg-active) !important;
        }

        .navbar .navbar-brand-wrapper {
            background: var(--qlhl-sidebar-bg) !important;
        }

        /* Dropdown menus stay light regardless of the dark navbar/sidebar */
        .navbar-dropdown,
        .navbar-dropdown .preview-subject,
        .navbar-dropdown h6 {
            color: var(--qlhl-text);
        }

        /* Cards, inputs, buttons: consistent rounding */
        .card,
        .dropdown-menu,
        .form-control,
        .input-group-text,
        .btn,
        .table {
            border-radius: var(--qlhl-radius);
        }

        .card {
            border: 1px solid var(--qlhl-border);
            box-shadow: 0 1px 2px rgba(59, 83, 62, 0.06);
        }

        .card {
            background: var(--qlhl-surface) !important;
            border: 1px solid var(--qlhl-border);
            box-shadow: 0 1px 2px rgba(59, 83, 62, 0.06);
            color: var(--qlhl-text);
        }

        .card .card-title {
            color: var(--qlhl-text);
        }

        .card .card-body {
            color: var(--qlhl-text);
        }

        .table,
        .table td,
        .table th,
        .table thead th {
            border-color: var(--qlhl-border) !important;
        }

        .btn-primary,
        .btn-gradient-primary {
            background: var(--qlhl-primary) !important;
            border-color: var(--qlhl-primary) !important;
        }

        .btn-primary:hover,
        .btn-gradient-primary:hover {
            background: var(--qlhl-primary-dark) !important;
            border-color: var(--qlhl-primary-dark) !important;
        }

        .btn-success {
            background: var(--qlhl-primary) !important;
            border-color: var(--qlhl-primary) !important;
            color: #fff !important;
        }

        .btn-success:hover {
            background: var(--qlhl-primary-dark) !important;
            border-color: var(--qlhl-primary-dark) !important;
        }

        .main-panel,
        .content-wrapper {
            background: var(--qlhl-bg) !important;
        }

        .content-wrapper {
            padding: 1.5rem;
        }

        .footer {
            background: transparent;
            border-top: 1px solid var(--qlhl-border);
            font-size: 0.8rem;
        }

        /* ===== Bảng dữ liệu (Kế hoạch học kỳ, v.v.) ===== */
        .table-responsive {
            border: 1px solid var(--qlhl-border);
            border-radius: var(--qlhl-radius);
            overflow-x: auto;
            overflow-y: hidden;
        }

        .table {
            background: var(--qlhl-surface);
            margin-bottom: 0;
        }

        .table thead th {
            background: #EEF2EC;
            color: var(--qlhl-text-muted);
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: .7rem .75rem;
            border-top: 0;
        }

        .table td {
            padding: .7rem .75rem;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #FAFBF9;
        }

        /* Badge trạng thái */
        .badge-status {
            display: inline-block;
            font-weight: 600;
            font-size: .72rem;
            padding: .3em .65em;
            border-radius: 999px;
        }

        .badge-status.status-draft {
            background: #E9ECE7;
            color: #5A6656;
        }

        .badge-status.status-submitted {
            background: #DCE7DA;
            color: #3B533E;
        }

        .badge-status.status-approved {
            background: #D7E9D8;
            color: #2E4030;
        }

        .badge-status.status-rejected {
            background: #F6DADA;
            color: #8A3A3A;
        }

        .badge-status.status-returned {
            background: #FBEBCF;
            color: #7A5A16;
        }

        .badge-status.status-legacy {
            background: #FBEBCF;
            color: #7A5A16;
        }

        /* Bootstrap 4 chưa có gap-* */
        .gap-2 {
            gap: .5rem;
        }
    </style>
    <link rel="shortcut icon" href="{{ $resourceAsset('images/favicon.png') }}" />
</head>

<body>
    <div class="container-scroller">
        <!-- partial:partials/_sidebar.html -->
        @include('partials._sidebar')
        <!-- partial -->
        <div class="container-fluid page-body-wrapper">
            <!-- partial:partials/_navbar.html -->
            @include('partials._navbar')
            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    @yield('content')
                </div>
                <!-- content-wrapper ends -->
                <!-- partial:partials/_footer.html -->
                @include('partials._footer')
                <!-- partial -->
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <script>
        @foreach ($dashboardScripts as $dashboardScript)
            {!! $resourceInlineJs($dashboardScript) !!}
        @endforeach
    </script>
    @stack('scripts')
</body>

</html>
