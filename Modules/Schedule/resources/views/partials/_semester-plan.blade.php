<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Ke hoach hoc ky</h5>
                        <small class="text-muted">Danh sach ke hoach hoc ky va thao tac UC4.</small>
                    </div>
                    @if ($canReviewWorkflow)
                        <a href="{{ route('schedule.create') }}" class="btn btn-sm btn-outline-primary">Tao ke hoach</a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Ten ke hoach</th>
                                <th>Khoa dao tao</th>
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
                                @php
                                    $canSubmitPlan = $canReviewWorkflow && in_array($schedule->status, ['draft', 'returned', 'rejected'], true);
                                    $canLeadershipReviewPlan = $canLeadershipReview && $schedule->status === 'submitted' && $schedule->current_step === 'leadership_review';
                                @endphp
                                <tr>
                                    <td>{{ $schedule->id }}</td>
                                    <td>
                                        <div class="font-weight-bold">{{ $schedule->name }}</div>
                                        @if ($schedule->description)
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($schedule->description, 90) }}</small>
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
                                    <td><span class="badge {{ $statusClass }}">{{ $schedule->status }}</span></td>
                                    <td>{{ $schedule->current_step }}</td>
                                    <td>{{ $schedule->submittedBy?->name ?? '-' }}</td>
                                    <td>{{ optional($schedule->submitted_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            {{-- Edit Button --}}
                                            @if ($canReviewWorkflow && in_array($schedule->status, ['draft'], true))
                                                <a href="{{ route('schedule.edit', $schedule->id) }}"
                                                    class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-edit"></i> Sua
                                                </a>
                                            @endif

                                            {{-- Delete Button --}}
                                            @if ($canReviewWorkflow && in_array($schedule->status, ['draft', 'returned'], true))
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
                                            @if ($canSubmitPlan)
                                                <form method="POST"
                                                    action="{{ route('schedule.submit', $schedule->id) }}"
                                                    style="flex: 1; min-width: 280px;">
                                                    @csrf
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" class="form-control" name="comment"
                                                            placeholder="Ghi chu trinh duyet (optional)">
                                                        <div class="input-group-append">
                                                            <button type="submit" class="btn btn-primary">UC4 Trinh duyet</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            @endif

                                            {{-- Ban Giam hieu duyet --}}
                                            @if ($canLeadershipReviewPlan)
                                                <div class="d-flex flex-column" style="flex: 1; min-width: 280px;">
                                                    <form method="POST"
                                                        action="{{ route('schedule.leadership-review', $schedule->id) }}"
                                                        class="mb-2">
                                                        @csrf
                                                        <input type="hidden" name="action" value="approve">
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control" name="comment"
                                                                placeholder="Nhan xet phe duyet (optional)">
                                                            <div class="input-group-append">
                                                                <button type="submit" class="btn btn-success">BGH Phe duyet</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                    <form method="POST"
                                                        action="{{ route('schedule.leadership-review', $schedule->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="action" value="reject">
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control" name="reason"
                                                                required placeholder="Ly do tu choi (required)">
                                                            <div class="input-group-append">
                                                                <button type="submit" class="btn btn-danger">BGH Tu choi</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            @endif

                                            @if (! $canSubmitPlan && ! $canLeadershipReviewPlan)
                                                @if (! $canReviewWorkflow && ! $canLeadershipReview)
                                                    <span class="text-muted text-nowrap">Khong co quyen thao tac</span>
                                                @else
                                                    <span class="text-muted text-nowrap">Khong co thao tac phu hop</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Chua co ke hoach hoc ky.</td>
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
