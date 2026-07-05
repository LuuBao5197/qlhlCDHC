@extends('layouts.dashboard')

@section('title', 'Thu hồi / đề nghị hủy yêu cầu hỗ trợ')

@section('content')
    @php
        $isDirectWithdraw = $supportRequest->status === 'pending_pdt';
        $targetAction = $isDirectWithdraw
            ? route('teaching-support-requests.withdraw', $supportRequest->id)
            : route('teaching-support-change-requests.store', $supportRequest->id);
        $submitLabel = $isDirectWithdraw ? 'Thu hồi yêu cầu' : 'Gửi phiếu đề nghị hủy';
        $helperText = $isDirectWithdraw
            ? 'Yêu cầu đang chờ PDT. Khoa có thể thu hồi trực tiếp và bắt buộc nhập lý do.'
            : 'Yêu cầu đã qua PDT. Khoa chỉ có thể gửi phiếu đề nghị hủy để PDT duyệt hoặc từ chối.';
    @endphp

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <h4 class="card-title text-primary font-weight-bold mb-1">
                        <i class="fas fa-ban mr-2"></i>Thu hồi / đề nghị hủy yêu cầu hỗ trợ
                    </h4>
                    <div class="text-muted">
                        Yêu cầu gốc #{{ $supportRequest->id }} -
                        {{ $supportRequest->requestingDepartment?->name ?? '-' }}
                        &rarr;
                        {{ $supportRequest->assignedSupportingDepartment?->name ?? $supportRequest->proposedSupportingDepartment?->name ?? '-' }}
                    </div>
                </div>
                <a href="{{ route('teaching-support-requests.show', $supportRequest->id) }}" class="btn btn-outline-secondary btn-sm">
                    Quay lại
                </a>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="alert alert-warning mb-3">
                {{ $helperText }}
            </div>
            <div class="row mb-3">
                <div class="col-md-4 mb-2">
                    <div class="small text-muted">Trạng thái</div>
                    <div class="font-weight-bold">{{ $supportRequest->status }}</div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="small text-muted">Người gửi</div>
                    <div class="font-weight-bold">{{ $supportRequest->submittedBy?->name ?? '-' }}</div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="small text-muted">Lưu ý</div>
                    <div>Chỉ thu hồi toàn bộ yêu cầu, không sửa từng tiết.</div>
                </div>
            </div>

            <form method="POST" action="{{ $targetAction }}">
                @csrf
                <div class="form-group">
                    <label class="font-weight-bold">Lý do thu hồi / đề nghị hủy</label>
                    <textarea name="reason" class="form-control" rows="4" maxlength="2000" required>{{ old('reason') }}</textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <a href="{{ route('teaching-support-requests.show', $supportRequest->id) }}" class="btn btn-outline-secondary">Hủy</a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-paper-plane mr-1"></i>{{ $submitLabel }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
