@php
    /** @var \App\Models\AdminBackfillLog|null $adminBackfillLog */
    $adminBackfillLog = $adminBackfillLog ?? null;
@endphp
@if ($adminBackfillLog)
    <div class="alert alert-warning admin-backfill-badge" style="padding:8px 12px; font-size:13px; border-radius:6px; background:#fff3cd; border:1px solid #ffe69c; color:#664d03;">
        <strong>⚠ Dữ liệu cũ do admin bổ sung</strong>
        — {{ $adminBackfillLog->admin?->name ?? 'Admin' }}
        lúc {{ $adminBackfillLog->created_at?->format('d/m/Y H:i') }}.
        <br>
        <span>Lý do: {{ $adminBackfillLog->reason }}</span>
    </div>
@endif
