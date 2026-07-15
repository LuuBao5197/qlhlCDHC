<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Ngày</th>
                <th>Tiết</th>
                <th>Lớp</th>
                <th>Giáo viên</th>
                <th>Môn/Bài học</th>
                <th>Phòng</th>
                <th>Quân số</th>
                <th>Vắng</th>
                <th>Xếp loại</th>
                <th>Nhận xét</th>
            </tr>
        </thead>
        <tbody>
            @forelse($details as $item)
                <tr>
                    <td>{{ optional(\Illuminate\Support\Carbon::parse($item->date))->format('d/m/Y') }}</td>
                    <td>{{ $item->period_number ?? $item->period ?? '-' }}</td>
                    <td>{{ $item->class_name ?? '-' }}</td>
                    <td>{{ $item->teacher_name ?? '-' }}</td>
                    <td>{{ trim(($item->subject_name ?? '-') . ' / ' . ($item->lesson_title ?? '-'), ' /') }}</td>
                    <td>{{ $item->room_name ?? '-' }}</td>
                    <td>{{ number_format((int) ($item->attendance_count ?? 0)) }}</td>
                    <td>{{ number_format((int) ($item->absent_count ?? 0)) }}</td>
                    <td>
                        <span class="badge badge-{{ $item->rating_level === 'yeu' ? 'danger' : ($item->rating_level === 'trung_binh' ? 'warning' : 'success') }}">
                            {{ $ratingLabels[$item->rating_level] ?? $item->rating_level }}
                        </span>
                    </td>
                    <td>{{ $item->comment ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Không có dữ liệu</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $details->links() }}
