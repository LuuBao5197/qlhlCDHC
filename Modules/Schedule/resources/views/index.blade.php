@extends('layouts.dashboard')

@section('title', 'Man hinh can bo phong dao tao')

@section('content')
    @php
        $user = auth()->user();
        $canReviewWorkflow = $user && ($user->isTrainingOffice() || $user->isAdmin());
        $canDepartmentAssign = $user && ($user->isDepartmentStaff() || $user->isAdmin());

        $statusStyles = [
            'draft' => 'badge-secondary',
            'pending' => 'badge-warning',
            'processing' => 'badge-info',
            'submitted' => 'badge-primary',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            'returned' => 'badge-dark',
            'resolved' => 'badge-success',
        ];

        $formatJson = static function ($payload): string {
            if (!is_array($payload)) {
                return '-';
            }

            return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-';
        };
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-1">Dieu hanh quy trinh lich huan luyen</h4>
                    <p class="text-muted mb-0">
                        UC4: trinh duyet ke hoach hoc ky, UC5: phe duyet lich thang, UC6: phe duyet phieu thay doi,
                        UC7: trinh lich thang len BGH.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (!$canReviewWorkflow && !$canDepartmentAssign)
        <div class="alert alert-warning">
            Tai khoan cua ban chi co quyen xem. Chuc nang nghiep vu chi danh cho vai tro `department_staff`,
            `training_office` hoac `admin`.
        </div>
    @endif

    @include('schedule::partials._semester-plan')

    @include('schedule::partials._monthly-schedule-list')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-1">Phieu de nghi thay doi ke hoach giang day</h5>
                    <small class="text-muted">UC6 phe duyet hoac tu choi phieu de nghi thay doi.</small>

                    @include('schedule::partials._change-request-create')

                    @include('schedule::partials._change-request-list')
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- REMOVED: large semester-plan, monthly-schedule-list, change-request bloc
