@extends('layouts.dashboard')

@section('title', 'Chi tiết hồ sơ phân công tháng tổng hợp')

@section('content')
    @php
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
        $totalSlotCount = $dossier->batches->sum(fn ($batch) => $batch->batchSlots->count() ?? 0);
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-left-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                        <div>
                            <h4 class="card-title mb-1 text-primary font-weight-bold">
                                <i class="fas fa-folder-open mr-2"></i>Hồ sơ phân công tháng tổng hợp
                            </h4>
                            <p class="mb-0 text-muted">
                                Tháng/Năm: <strong class="text-dark">{{ str_pad((string) $dossier->month, 2, '0', STR_PAD_LEFT) }}/{{ $dossier->year }}</strong>
                                <span class="mx-2">|</span>
                                Trạng thái: <span class="badge {{ $statusClassesDossier[$dossier->status] ?? 'badge-secondary' }}">{{ $statusLabelsDossier[$dossier->status] ?? $dossier->status }}</span>
                                <span class="mx-2">|</span>
                                Bước hiện tại: <strong>{{ $stepLabelsDossier[$dossier->current_step] ?? $dossier->current_step }}</strong>
                            </p>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <a href="{{ route('monthly-assignment-dossiers.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
                            </a>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Người tạo</div>
                                <div class="font-weight-bold">{{ $dossier->createdBy?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Người gửi</div>
                                <div class="font-weight-bold">{{ $dossier->submittedBy?->name ?? '-' }}</div>
                                <div class="small text-muted">{{ optional($dossier->submitted_at)->format('d/m/Y H:i') ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Lãnh đạo PĐT duyệt</div>
                                <div class="font-weight-bold">{{ $dossier->reviewedBy?->name ?? '-' }}</div>
                                <div class="small text-muted">{{ optional($dossier->reviewed_at)->format('d/m/Y H:i') ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Số Khoa / Tổng slot</div>
                                <div class="h5 mb-0">{{ $dossier->batches->count() }} / {{ $totalSlotCount }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        @if ($canSubmit ?? false)
                            <form method="POST" action="{{ route('monthly-assignment-dossiers.submit', $dossier->id) }}" class="d-inline-block mr-2 mb-2"
                                onsubmit="return confirm('Gửi hồ sơ tổng hợp này lên Lãnh đạo Phòng Đào tạo?');">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-paper-plane mr-1"></i>Gửi Lãnh đạo PĐT
                                </button>
                            </form>
                        @endif

                        @if ($canTrainingOfficeReview ?? false)
                            <form method="POST" action="{{ route('monthly-assignment-dossiers.training-office-review', $dossier->id) }}" class="d-inline-block mr-2 mb-2">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Duyệt hồ sơ và trình BGH?');">
                                    <i class="fas fa-check mr-1"></i>PĐT Duyệt & trình BGH
                                </button>
                            </form>
                            <form method="POST" action="{{ route('monthly-assignment-dossiers.training-office-review', $dossier->id) }}" class="d-inline-block mb-2">
                                @csrf
                                <input type="hidden" name="action" value="reject">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" name="reason" required placeholder="Lý do từ chối (bắt buộc)">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-danger">PĐT Từ chối</button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        @if ($canLeadershipReview ?? false)
                            <form method="POST" action="{{ route('monthly-assignment-dossiers.leadership-review', $dossier->id) }}" class="d-inline-block mr-2 mb-2">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('BGH phê duyệt hồ sơ này?');">
                                    <i class="fas fa-check mr-1"></i>BGH Phê duyệt
                                </button>
                            </form>
                            <form method="POST" action="{{ route('monthly-assignment-dossiers.leadership-review', $dossier->id) }}" class="d-inline-block mb-2">
                                @csrf
                                <input type="hidden" name="action" value="reject">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" name="reason" required placeholder="Lý do từ chối (bắt buộc)">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-danger">BGH Từ chối</button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        @if (! ($canSubmit ?? false) && ! ($canTrainingOfficeReview ?? false) && ! ($canLeadershipReview ?? false))
                            <span class="text-muted">Không có thao tác phù hợp ở trạng thái/quyền hiện tại.</span>
                        @endif
                    </div>
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

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3 text-dark font-weight-bold">Danh sách batch của các Khoa trong hồ sơ</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Khoa</th>
                            <th>Số slot</th>
                            <th>Người gửi (Khoa)</th>
                            <th>Lãnh đạo Khoa duyệt</th>
                            <th>PĐT duyệt</th>
                            <th>Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dossier->batches as $batch)
                            <tr>
                                <td>{{ $batch->department?->name ?? '-' }}</td>
                                <td>{{ $batch->batchSlots->count() }}</td>
                                <td>{{ $batch->submittedBy?->name ?? '-' }}</td>
                                <td>{{ $batch->departmentReviewedBy?->name ?? '-' }}</td>
                                <td>{{ $batch->trainingOfficeReviewedBy?->name ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('department-monthly-assignment-batches.show', $batch->id) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye mr-1"></i>Xem
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Chưa có batch nào trong hồ sơ.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
