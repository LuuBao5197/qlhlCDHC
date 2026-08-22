@extends('layouts.dashboard')

@section('title', 'Hồ sơ phân công tháng tổng hợp')

@section('content')
    @php
        $adminBackfillEligible = auth()->user()?->isAdmin() === true;
        $adminBackfillMode = $adminBackfillEligible && request()->boolean('admin_backfill');
        $statusLabelsDossier = [
            'draft' => 'Nháp',
            'submitted' => 'Đã gửi',
            'approved' => 'Đã phê duyệt',
            'rejected' => 'Bị từ chối',
            'returned' => 'Trả về chỉnh sửa',
        ];
        $stepLabelsDossier = [
            'draft' => '-',
            'training_office_review' => 'Chờ Lãnh đạo PĐT duyệt',
            'leadership_review' => 'Chờ BGH duyệt',
            'completed' => 'Hoàn tất',
        ];
        $statusClassesDossier = [
            'draft' => 'badge-secondary',
            'submitted' => 'badge-info',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            'returned' => 'badge-warning',
        ];
        $readinessBadgeClasses = [
            'missing' => 'badge-secondary',
            'pending_department_review' => 'badge-info',
            'pending_training_office_review' => 'badge-info',
            'returned' => 'badge-warning',
            'ready' => 'badge-success',
        ];
        $isComplete = $readiness['is_complete'] ?? false;
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-left-primary shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-1 text-primary font-weight-bold">
                        <i class="fas fa-folder-open mr-2"></i>Hồ sơ phân công tháng tổng hợp
                    </h4>
                    <p class="mb-0 text-muted">
                        PĐT tổng hợp các batch phân công đã được Lãnh đạo Khoa và PĐT duyệt, gửi Lãnh đạo PĐT rồi BGH phê duyệt.
                    </p>

                    <form method="GET" action="{{ route('monthly-assignment-dossiers.index') }}" class="mt-3">
                        <div class="row align-items-end">
                            <div class="col-md-2 mb-2">
                                <label class="mb-1 font-weight-bold">Tháng</label>
                                <select name="month" class="form-control">
                                    @foreach (range(1, 12) as $m)
                                        <option value="{{ $m }}" @selected((int) $month === $m)>{{ str_pad((string) $m, 2, '0', STR_PAD_LEFT) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="mb-1 font-weight-bold">Năm</label>
                                <input type="number" name="year" class="form-control" value="{{ $year }}" min="2020" max="2100">
                            </div>
                            <div class="col-md-3 mb-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter mr-1"></i>Xem tình trạng tháng này
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong><i class="fas fa-exclamation-triangle mr-1"></i>Có lỗi:</strong>
            <ul class="mb-0 pl-3 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-dark font-weight-bold">
                    Tình trạng các Khoa - tháng {{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}
                </h5>
                @if ($canBuild)
                    <form method="POST" action="{{ route('monthly-assignment-dossiers.store') }}"
                        onsubmit="return confirm({{ $adminBackfillMode ? "'Bo sung du lieu cu: tao ho so va DUYET NGAY qua ca PDT va BGH, khong can cho duyet thu cong?'" : "'Tạo/làm mới bản nháp hồ sơ tổng hợp cho tháng {{ $month }}/{{ $year }}?'" }});">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        @if ($adminBackfillMode)
                            <input type="hidden" name="admin_backfill" value="1">
                            <input type="hidden" name="admin_backfill_reason" value="{{ old('admin_backfill_reason') }}" id="dossierAdminBackfillReasonHidden">
                        @endif
                        <button type="submit" class="btn btn-success btn-sm" @disabled(! $isComplete)>
                            <i class="fas fa-layer-group mr-1"></i>{{ $adminBackfillMode ? 'Tạo và duyệt nhanh (dữ liệu cũ)' : 'Tạo/làm mới bản nháp tổng hợp' }}
                        </button>
                    </form>
                @endif
            </div>

            @if ($adminBackfillEligible)
                <div class="alert alert-warning" style="border:1px dashed #b98900;">
                    @if (! $adminBackfillMode)
                        <a href="{{ request()->fullUrlWithQuery(['admin_backfill' => 1]) }}" class="btn btn-sm btn-outline-warning">
                            Bật chế độ bổ sung dữ liệu cũ (tạo hồ sơ và duyệt luôn qua PĐT + BGH)
                        </a>
                    @else
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Đang ở chế độ bổ sung dữ liệu cũ — khi tạo hồ sơ sẽ được duyệt ngay (bỏ qua PĐT/BGH thủ công).</strong>
                            <a href="{{ request()->fullUrlWithQuery(['admin_backfill' => null]) }}" class="btn btn-sm btn-outline-secondary">Tắt</a>
                        </div>
                        <label class="form-label">Lý do bổ sung dữ liệu cũ <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('admin_backfill_reason') is-invalid @enderror" rows="2"
                            placeholder="Vi du: Bo sung ho so phan cong giang day truoc khi he thong van hanh..."
                            oninput="var el = document.getElementById('dossierAdminBackfillReasonHidden'); if (el) { el.value = this.value; }">{{ old('admin_backfill_reason') }}</textarea>
                        @error('admin_backfill_reason')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    @endif
                </div>
            @endif

            @if (! $isComplete)
                <div class="alert alert-warning mb-3">
                    Chưa đủ Khoa để tổng hợp. Danh sách bên dưới liệt kê Khoa nào còn thiếu và trạng thái hiện tại.
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Khoa</th>
                            <th>Trạng thái batch</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($readiness['departments'] as $item)
                            <tr>
                                <td>{{ $item['department_name'] }}</td>
                                <td>
                                    <span class="badge {{ $readinessBadgeClasses[$item['readiness_status']] ?? 'badge-secondary' }}">
                                        {{ $statusLabels[$item['readiness_status']] ?? $item['readiness_status'] }}
                                    </span>
                                    @if ($item['batch_id'])
                                        <a href="{{ route('department-monthly-assignment-batches.show', $item['batch_id']) }}" class="small ml-2">Xem batch</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">Tháng này chưa có Khoa nào có tiết môn học.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3 text-dark font-weight-bold">Danh sách hồ sơ tổng hợp</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Tháng/Năm</th>
                            <th>Trạng thái</th>
                            <th>Bước hiện tại</th>
                            <th>Người gửi</th>
                            <th>Thời gian gửi</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dossiers as $dossier)
                            <tr>
                                <td>{{ str_pad((string) $dossier->month, 2, '0', STR_PAD_LEFT) }}/{{ $dossier->year }}</td>
                                <td><span class="badge {{ $statusClassesDossier[$dossier->status] ?? 'badge-secondary' }}">{{ $statusLabelsDossier[$dossier->status] ?? $dossier->status }}</span></td>
                                <td>{{ $stepLabelsDossier[$dossier->current_step] ?? $dossier->current_step }}</td>
                                <td>{{ $dossier->submittedBy?->name ?? '-' }}</td>
                                <td>{{ optional($dossier->submitted_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('monthly-assignment-dossiers.show', $dossier->id) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye mr-1"></i>Xem
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Chưa có hồ sơ tổng hợp nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $dossiers->links() }}</div>
        </div>
    </div>
@endsection
