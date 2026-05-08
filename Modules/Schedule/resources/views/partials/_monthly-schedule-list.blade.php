<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Lich phan cong giang day theo thang</h5>
                        <small class="text-muted">Khoa phan cong lich thang, gui PDT duyet; PDT thuc hien UC5 va UC7.</small>
                    </div>
                    @if ($user && ($user->isTrainingOffice() || $user->isAdmin()))
                        <a href="{{ route('monthly-schedule.initialize.form') }}" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-plus"></i> Khoi tao lich thang
                        </a>
                    @endif
                </div>

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
                                    <td><span class="badge {{ $statusClass }}">{{ $monthlySchedule->status }}</span></td>
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
                                                                    <button type="submit" class="btn btn-primary">Gui PDT duyet</button>
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
                                                                    <button type="submit" class="btn btn-success">UC5 Phe duyet</button>
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
                                                                    <button type="submit" class="btn btn-danger">UC5 Tu choi</button>
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
                                                                    <button type="submit" class="btn btn-primary">UC7 Trinh len BGH</button>
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
