<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="card-title mb-1">Kế hoạch học kỳ</h5>
                        <small class="text-muted">Danh sách kế hoạch học kỳ.</small>
                    </div>
                    @if ($canManageSemesterPlan)
                        <a href="{{ route('schedule.create') }}" class="btn btn-sm btn-primary">Tạo kế hoạch</a>
                    @endif
                </div>

                @php
                    $statusLabels = [
                        'draft' => 'Bản thảo',
                        'submitted' => 'Đã trình',
                        'returned' => 'Trả lại',
                        'rejected' => 'Từ chối',
                        'approved' => 'Đã duyệt',
                    ];
                    $stepLabels = [
                        'draft' => 'Chưa trình duyệt',
                        'training_office_review' => 'Phòng Đào tạo duyệt',
                        'leadership_review' => 'Ban Giám hiệu duyệt',
                        'completed' => 'Hoàn tất',
                    ];
                @endphp

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 56px;">#</th>
                                <th>Tên kế hoạch</th>
                                <th>Khoa đào tạo</th>
                                <th style="width: 100px;">Học kỳ</th>
                                <th style="width: 200px;">Trạng thái</th>
                                <th style="width: 170px;">Người trình</th>
                                <th style="width: 160px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schedules as $schedule)
                                @php
                                    $statusClass = 'status-' . $schedule->status;
                                    $canSubmitPlan =
                                        $canManageSemesterPlan &&
                                        in_array($schedule->status, ['draft', 'returned', 'rejected'], true);
                                    $canTrainingOfficeReviewPlan =
                                        $canReviewWorkflow &&
                                        $schedule->status === 'submitted' &&
                                        $schedule->current_step === 'training_office_review';
                                    $canLeadershipReviewPlan =
                                        $canLeadershipReview &&
                                        $schedule->status === 'submitted' &&
                                        $schedule->current_step === 'leadership_review';
                                    $isAdminBackfilledPlan = $schedule->adminBackfillLog !== null;
                                    $canEditPlan =
                                        $canManageSemesterPlan &&
                                        (in_array($schedule->status, ['draft'], true) ||
                                            ($isAdminBackfilledPlan && $user?->isAdmin()));
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
                                    <td>
                                        @if ($schedule->trainingBatch)
                                            <div class="font-weight-bold">{{ $schedule->trainingBatch->code }}</div>
                                            <small class="text-muted">
                                                {{ $schedule->trainingBatch->name }}
                                                @if ($schedule->trainingBatch->trainingProgram)
                                                    - {{ $schedule->trainingBatch->trainingProgram->code }}
                                                @endif
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>HK {{ $schedule->semester }} / {{ $schedule->year }}</td>
                                    <td>
                                        <span class="badge-status {{ $statusClass }}">
                                            {{ $statusLabels[$schedule->status] ?? $schedule->status }}
                                        </span>
                                        @if ($isAdminBackfilledPlan)
                                            <span class="badge-status status-legacy"
                                                title="{{ $schedule->adminBackfillLog->reason }}">
                                                Dữ liệu cũ
                                            </span>
                                        @endif
                                        <div class="mt-1">
                                            <small
                                                class="text-muted">{{ $stepLabels[$schedule->current_step] ?? $schedule->current_step }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $schedule->submittedBy?->name ?? '-' }}</div>
                                        @if ($schedule->submitted_at)
                                            <small
                                                class="text-muted">{{ $schedule->submitted_at->format('d/m/Y H:i') }}</small>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            {{-- View overview schedule --}}
                                            <a href="{{ route('schedule.semester', ['semester' => $schedule->semester, 'year' => $schedule->year, 'training_batch_id' => $schedule->training_batch_id]) }}"
                                                class="btn btn-sm btn-outline-info" title="Xem lịch tổng quát học kỳ" target="_blank">
                                                <i class="mdi mdi-calendar-month"></i>
                                            </a>

                                            {{-- Edit --}}
                                            @if ($canEditPlan)
                                                <a href="{{ route('schedule.edit', $schedule->id) }}"
                                                    class="btn btn-sm btn-outline-secondary" title="Sửa">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                            @endif

                                            {{-- Delete --}}
                                            @if ($canManageSemesterPlan && in_array($schedule->status, ['draft', 'returned'], true))
                                                <form method="POST"
                                                    action="{{ route('schedule.destroy', $schedule->id) }}"
                                                    class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Xóa kế hoạch"
                                                        onclick="return confirm('Bạn có chắc muốn xóa kế hoạch này, tất cả những dữ liệu liên quan sẽ bị ảnh hưởng.');">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Submit --}}
                                            @if ($canSubmitPlan)
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    data-toggle="modal" data-target="#mdSubmit{{ $schedule->id }}">
                                                    Trình duyệt
                                                </button>
                                            @endif

                                            {{-- Training office review --}}
                                            @if ($canTrainingOfficeReviewPlan)
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    data-toggle="modal"
                                                    data-target="#mdTrainingReview{{ $schedule->id }}">
                                                    Xử lý
                                                </button>
                                            @endif

                                            {{-- Leadership review --}}
                                            @if ($canLeadershipReviewPlan)
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    data-toggle="modal"
                                                    data-target="#mdLeadershipReview{{ $schedule->id }}">
                                                    Xử lý
                                                </button>
                                            @endif

                                            @if (!$canSubmitPlan && !$canTrainingOfficeReviewPlan && !$canLeadershipReviewPlan && !$canEditPlan)
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- ===== Modal: Trình Phòng Đào tạo ===== --}}
                                @if ($canSubmitPlan)
                                    <div class="modal fade" id="mdSubmit{{ $schedule->id }}" tabindex="-1"
                                        role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <form method="POST"
                                                action="{{ route('schedule.submit', $schedule->id) }}">
                                                @csrf
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Trình Phòng Đào tạo</h5>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-muted">Kế hoạch:
                                                            <strong>{{ $schedule->name }}</strong>
                                                        </p>
                                                        <div class="form-group">
                                                            <label>Ghi chú trình duyệt (tùy chọn)</label>
                                                            <input type="text" class="form-control" name="comment"
                                                                placeholder="Ghi chú trình duyệt">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary"
                                                            data-dismiss="modal">Hủy</button>
                                                        <button type="submit" class="btn btn-primary">Trình Phòng Đào
                                                            tạo</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif

                                {{-- ===== Modal: Phòng Đào tạo duyệt ===== --}}
                                @if ($canTrainingOfficeReviewPlan)
                                    <div class="modal fade" id="mdTrainingReview{{ $schedule->id }}" tabindex="-1"
                                        role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Phòng Đào tạo xử lý</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-muted mb-3">Kế hoạch:
                                                        <strong>{{ $schedule->name }}</strong>
                                                    </p>

                                                    <form method="POST"
                                                        action="{{ route('schedule.training-office-review', $schedule->id) }}"
                                                        class="mb-4">
                                                        @csrf
                                                        <input type="hidden" name="action" value="approve">
                                                        <div class="form-group">
                                                            <label>Nhận xét phê duyệt (tùy chọn)</label>
                                                            <input type="text" class="form-control" name="comment"
                                                                placeholder="Nhận xét phê duyệt">
                                                        </div>
                                                        <button type="submit" class="btn btn-success btn-block">Duyệt
                                                            và trình BGH</button>
                                                    </form>

                                                    <hr>

                                                    <form method="POST"
                                                        action="{{ route('schedule.training-office-review', $schedule->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="action" value="reject">
                                                        <div class="form-group">
                                                            <label>Lý do từ chối <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" name="reason"
                                                                required placeholder="Lý do từ chối">
                                                        </div>
                                                        <button type="submit" class="btn btn-danger btn-block">Từ
                                                            chối</button>
                                                    </form>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        data-dismiss="modal">Đóng</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- ===== Modal: Ban Giám hiệu duyệt ===== --}}
                                @if ($canLeadershipReviewPlan)
                                    <div class="modal fade" id="mdLeadershipReview{{ $schedule->id }}"
                                        tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Ban Giám hiệu xử lý</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-muted mb-3">Kế hoạch:
                                                        <strong>{{ $schedule->name }}</strong>
                                                    </p>

                                                    <form method="POST"
                                                        action="{{ route('schedule.leadership-review', $schedule->id) }}"
                                                        class="mb-4">
                                                        @csrf
                                                        <input type="hidden" name="action" value="approve">
                                                        <div class="form-group">
                                                            <label>Nhận xét phê duyệt (tùy chọn)</label>
                                                            <input type="text" class="form-control" name="comment"
                                                                placeholder="Nhận xét phê duyệt">
                                                        </div>
                                                        <button type="submit" class="btn btn-success btn-block">BGH
                                                            Phê duyệt</button>
                                                    </form>

                                                    <hr>

                                                    <form method="POST"
                                                        action="{{ route('schedule.leadership-review', $schedule->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="action" value="reject">
                                                        <div class="form-group">
                                                            <label>Lý do từ chối <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" name="reason"
                                                                required placeholder="Lý do từ chối">
                                                        </div>
                                                        <button type="submit" class="btn btn-danger btn-block">BGH Từ
                                                            chối</button>
                                                    </form>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        data-dismiss="modal">Đóng</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Chưa có kế hoạch học kỳ.
                                    </td>
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
