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

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Ke hoach hoc ky</h5>
                            <small class="text-muted">Danh sach ke hoach hoc ky va thao tac UC4.</small>
                        </div>
                        <a href="{{ route('schedule.create') }}" class="btn btn-sm btn-outline-primary">Tao ke hoach</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">#</th>
                                    <th>Ten ke hoach</th>
                                    <th>Hoc ky/Nam</th>
                                    <th>Trang thai</th>
                                    <th>Current step</th>
                                    <th>Nguoi trinh</th>
                                    <th>Thoi diem trinh</th>
                                    <th style="min-width: 270px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($schedules as $schedule)
                                    @php
                                        $statusClass = $statusStyles[$schedule->status] ?? 'badge-light';
                                    @endphp
                                    <tr>
                                        <td>{{ $schedule->id }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $schedule->name }}</div>
                                            @if ($schedule->description)
                                                <small
                                                    class="text-muted">{{ \Illuminate\Support\Str::limit($schedule->description, 90) }}</small>
                                            @endif
                                        </td>
                                        <td>HK {{ $schedule->semester }} / {{ $schedule->year }}</td>
                                        <td><span class="badge {{ $statusClass }}">{{ $schedule->status }}</span></td>
                                        <td>{{ $schedule->current_step }}</td>
                                        <td>{{ $schedule->submittedBy?->name ?? '-' }}</td>
                                        <td>{{ optional($schedule->submitted_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                        <td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                {{-- Edit Button --}}
                                                @if (in_array($schedule->status, ['draft'], true))
                                                    <a href="{{ route('schedule.edit', $schedule->id) }}"
                                                        class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-edit"></i> Sua
                                                    </a>
                                                @endif

                                                {{-- Delete Button --}}
                                                @if (in_array($schedule->status, ['draft', 'returned'], true))
                                                <form method="POST"
                                                    action="{{ route('schedule.destroy', $schedule->id) }}"
                                                    style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Xoa ke hoach"
                                                        onclick="return confirm('Ban chac chan muon xoa ke hoach nay khong? Toan bo du lieu lien quan se bi xoa.');">
                                                        <i class="fas fa-trash"></i> Xoa
                                                    </button>
                                                </form>
                                                @endif

                                                {{-- UC4 Submit --}}
                                                @if ($canReviewWorkflow && in_array($schedule->status, ['draft', 'returned', 'rejected'], true))
                                                    <form method="POST"
                                                        action="{{ route('schedule.submit', $schedule->id) }}"
                                                        style="flex: 1; min-width: 280px;">
                                                        @csrf
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control" name="comment"
                                                                placeholder="Ghi chu trinh duyet (optional)">
                                                            <div class="input-group-append">
                                                                <button type="submit" class="btn btn-primary">UC4 Trinh
                                                                    duyet</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                @elseif (!$canReviewWorkflow)
                                                    <span class="text-muted text-nowrap">Khong co quyen thao tac</span>
                                                @else
                                                    <span class="text-muted text-nowrap">Khong co thao tac phu
                                                        hop</span>
                                                @endif
                                            </div>
                                        </td>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Chua co ke hoach hoc ky.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $schedules->links() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-1">Lich phan cong giang day theo thang</h5>
                    <small class="text-muted">Khoa phan cong lich thang, gui PDT duyet; PDT thuc hien UC5 va UC7.</small>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">ID</th>
                                    <th>Lop</th>
                                    <th>Thang/Nam</th>
                                    <th>Ke hoach</th>
                                    <th>Trang thai</th>
                                    <th>Phe duyet boi</th>
                                    <th>Ly do tu choi</th>
                                    <th style="min-width: 360px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($monthlySchedules as $monthlySchedule)
                                    @php
                                        $statusClass = $statusStyles[$monthlySchedule->status] ?? 'badge-light';
                                    @endphp
                                    <tr>
                                        <td>{{ $monthlySchedule->id }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $monthlySchedule->class_name }}</div>
                                            <small class="text-muted">
                                                Khoa: {{ $monthlySchedule->department?->name ?? '-' }}
                                            </small>
                                        </td>
                                        <td>{{ $monthlySchedule->month }}/{{ $monthlySchedule->year }}</td>
                                        <td>{{ $monthlySchedule->plan?->name ?? '-' }}</td>
                                        <td><span class="badge {{ $statusClass }}">{{ $monthlySchedule->status }}</span>
                                        </td>
                                        <td>{{ $monthlySchedule->approvedBy?->name ?? '-' }}</td>
                                        <td>{{ $monthlySchedule->rejection_reason ?? '-' }}</td>
                                        <td>
                                            @if ($canReviewWorkflow || $canDepartmentAssign)
                                                <div class="d-flex flex-column">
                                                    @if ($canDepartmentAssign)
                                                        <div class="mb-2">
                                                            <a href="{{ route('monthly-schedule.assignment', $monthlySchedule->id) }}"
                                                                class="btn btn-sm btn-outline-info">
                                                                Phan cong theo thang
                                                            </a>
                                                        </div>

                                                        @if (in_array($monthlySchedule->status, ['draft', 'returned', 'rejected', 'pending'], true))
                                                            <form method="POST"
                                                                action="{{ route('monthly-schedule.submit-training', $monthlySchedule->id) }}"
                                                                class="mb-2">
                                                                @csrf
                                                                <div class="input-group input-group-sm">
                                                                    <input type="text" class="form-control"
                                                                        name="comment"
                                                                        placeholder="Ghi chu gui PDT (optional)">
                                                                    <div class="input-group-append">
                                                                        <button type="submit" class="btn btn-primary">Gui
                                                                            PDT duyet</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        @endif
                                                    @endif

                                                    @if (in_array($monthlySchedule->status, ['pending', 'processing', 'submitted', 'returned'], true))
                                                        @if ($canReviewWorkflow)
                                                            <form method="POST"
                                                                action="{{ route('monthly-schedule.review', $monthlySchedule->id) }}"
                                                                class="mb-2">
                                                                @csrf
                                                                <input type="hidden" name="action" value="approve">
                                                                <div class="input-group input-group-sm">
                                                                    <input type="text" class="form-control"
                                                                        name="comment"
                                                                        placeholder="Nhan xet phe duyet (optional)">
                                                                    <div class="input-group-append">
                                                                        <button type="submit" class="btn btn-success">UC5
                                                                            Phe duyet</button>
                                                                    </div>
                                                                </div>
                                                            </form>

                                                            <form method="POST"
                                                                action="{{ route('monthly-schedule.review', $monthlySchedule->id) }}">
                                                                @csrf
                                                                <input type="hidden" name="action" value="reject">
                                                                <div class="input-group input-group-sm">
                                                                    <input type="text" class="form-control"
                                                                        name="reason" required
                                                                        placeholder="Ly do tu choi (required)">
                                                                    <div class="input-group-append">
                                                                        <button type="submit" class="btn btn-danger">UC5
                                                                            Tu choi</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        @endif
                                                    @elseif ($monthlySchedule->status === 'approved')
                                                        @if ($canReviewWorkflow)
                                                            <form method="POST"
                                                                action="{{ route('monthly-schedule.submit-leadership', $monthlySchedule->id) }}">
                                                                @csrf
                                                                <div class="input-group input-group-sm">
                                                                    <input type="text" class="form-control"
                                                                        name="comment"
                                                                        placeholder="Ghi chu trinh BGH (optional)">
                                                                    <div class="input-group-append">
                                                                        <button type="submit" class="btn btn-primary">UC7
                                                                            Trinh len BGH</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Khong co thao tac phu hop</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">Khong co quyen thao tac</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Chua co lich thang.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-1">Phieu de nghi thay doi ke hoach giang day</h5>
                    <small class="text-muted">UC6 phe duyet hoac tu choi phieu de nghi thay doi.</small>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">ID</th>
                                    <th>Lich thang</th>
                                    <th>Nguoi de nghi</th>
                                    <th>Trang thai</th>
                                    <th>Ly do</th>
                                    <th>Noi dung thay doi</th>
                                    <th style="min-width: 360px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($changeRequests as $changeRequest)
                                    @php
                                        $statusClass = $statusStyles[$changeRequest->status] ?? 'badge-light';
                                    @endphp
                                    <tr>
                                        <td>{{ $changeRequest->id }}</td>
                                        <td>
                                            @if ($changeRequest->monthlySchedule)
                                                Lop {{ $changeRequest->monthlySchedule->class_name }}
                                                ({{ $changeRequest->monthlySchedule->month }}/{{ $changeRequest->monthlySchedule->year }})
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $changeRequest->requestedBy?->name ?? '-' }}</td>
                                        <td><span class="badge {{ $statusClass }}">{{ $changeRequest->status }}</span>
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($changeRequest->reason, 80) }}</td>
                                        <td>
                                            <details>
                                                <summary class="text-primary">Xem old/new payload</summary>
                                                <div class="mt-2">
                                                    <div><strong>old_payload</strong></div>
                                                    <pre class="bg-dark text-light p-2 rounded">{{ $formatJson($changeRequest->old_payload) }}</pre>
                                                    <div><strong>new_payload</strong></div>
                                                    <pre class="bg-dark text-light p-2 rounded">{{ $formatJson($changeRequest->new_payload) }}</pre>
                                                </div>
                                            </details>
                                        </td>
                                        <td>
                                            @if ($canReviewWorkflow && $changeRequest->status === 'pending')
                                                <div class="d-flex flex-column">
                                                    <form method="POST"
                                                        action="{{ route('change-request.review', $changeRequest->id) }}"
                                                        class="mb-2">
                                                        @csrf
                                                        <input type="hidden" name="action" value="approve">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="apply_changes" value="1" checked
                                                                id="apply-{{ $changeRequest->id }}">
                                                            <label class="form-check-label"
                                                                for="apply-{{ $changeRequest->id }}">
                                                                Apply new_payload vao slot
                                                            </label>
                                                        </div>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control" name="comment"
                                                                placeholder="Nhan xet phe duyet (optional)">
                                                            <div class="input-group-append">
                                                                <button type="submit" class="btn btn-success">UC6 Phe
                                                                    duyet</button>
                                                            </div>
                                                        </div>
                                                    </form>

                                                    <form method="POST"
                                                        action="{{ route('change-request.review', $changeRequest->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="action" value="reject">
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control" name="reason"
                                                                required placeholder="Ly do tu choi (required)">
                                                            <div class="input-group-append">
                                                                <button type="submit" class="btn btn-danger">UC6 Tu
                                                                    choi</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            @elseif (!$canReviewWorkflow)
                                                <span class="text-muted">Khong co quyen thao tac</span>
                                            @else
                                                <span class="text-muted">Khong co thao tac phu hop</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Chua co phieu de nghi thay
                                            doi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
