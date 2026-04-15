<?php

namespace Modules\Schedule\Application\GetScheduleSemester;

use Carbon\Carbon;
use Modules\Schedule\Models\PlanTemplates;
use Modules\Schedule\Models\Plans;

class GetScheduleSemesterHandler
{
    public function handle(GetScheduleSemesterRequest $request)
    {
        $validated = $request->validated();
        $semester = $validated['semester'] ?? null;
        $year = $validated['year'] ?? null;
        $className = $validated['className'] ?? null;

        if (!$semester || !$year) return view('schedule::semester', ['plan' => null]);

        $plan = Plans::where('semester', $semester)->where('year', $year)->latest()->first();
        if (!$plan) return view('schedule::semester', ['plan' => null]);

        $planTemplates = PlanTemplates::with(['subjects'])
            ->where('plan_id', $plan->id)
            ->when($className, function ($q) use ($className) {
                $q->whereHas('classes', fn($c) => $c->where('code', $className));
            })->get();

        if ($planTemplates->isEmpty()) return view('schedule::semester', ['plan' => $plan, 'renderRows' => []]);

        $startDate = $planTemplates->min('start_date');
        $endDate = $planTemplates->max('end_date');

        $dates = [];
        $tempDate = $startDate->copy();
        while ($tempDate <= $endDate) {
            $dates[] = $tempDate->copy();
            $tempDate->addDay();
        }

        $periods = range(1, 9);
        $scheduleMatrix = [];

        // Đổ dữ liệu thô vào ma trận
        foreach ($planTemplates as $template) {
            $dows = is_array($template->days_of_week) ? $template->days_of_week : (json_decode($template->days_of_week, true) ?: [$template->day_of_week]);
            $pRange = $this->parsePeriodRange($template->period_range ?: ($template->session === 'Sáng' ? '1-5' : '6-9'));

            foreach ($dates as $date) {
                $dow = $date->dayOfWeekIso + 1;
                if ($date >= $template->start_date && $date <= $template->end_date && in_array($dow, $dows)) {
                    foreach ($pRange as $p) {
                        $scheduleMatrix[$p][$date->toDateString()] = [
                            'subject' => $template->subjects?->name ?? 'Môn học',
                            'session' => $template->session,
                            'description' => $template->description,
                        ];
                    }
                }
            }
        }

        $renderRows = [];
        $assigned = [];

        // HÀM HELPER: So sánh nội dung để gộp
        $isSameContent = function ($cell1, $cell2) {
            if (!$cell1 || !$cell2) return false;
            return $cell1['subject'] === $cell2['subject'];
        };

        // Quét ma trận để tính gộp ô
        foreach ($periods as $p) {
            foreach ($dates as $index => $date) {
                $dateKey = $date->toDateString();

                // 🔴 ĐIỂM SỬA LỖI QUAN TRỌNG NHẤT Ở ĐÂY:
                // Nếu ô đã bị gộp (nằm dưới quyền của ô khác), bắt buộc phải gán 'type' = 'hidden'
                // để Blade file gọi @continue và không render thẻ <td> nào cả.
                if (isset($assigned[$p][$dateKey])) {
                    $renderRows[$p][$dateKey] = ['type' => 'hidden'];
                    continue;
                }

                $current = $scheduleMatrix[$p][$dateKey] ?? null;

                if (!$current) {
                    $renderRows[$p][$dateKey] = ['type' => 'empty', 'colspan' => 1, 'rowspan' => 1];
                    continue;
                }

                // 1. Tính Rowspan (Gộp dọc)
                $rowspan = 1;
                for ($rp = $p + 1; $rp <= max($periods); $rp++) {
                    if (isset($scheduleMatrix[$rp][$dateKey]) && $isSameContent($scheduleMatrix[$rp][$dateKey], $current)) {
                        $rowspan++;
                    } else { break; }
                }

                // 2. Tính Colspan (Gộp ngang)
                $colspan = 1;
                for ($di = $index + 1; $di < count($dates); $di++) {
                    $nextDateKey = $dates[$di]->toDateString();
                    $canMerge = true;
                    // Check xem toàn bộ các tiết của cột tiếp theo có giống hệt nội dung không
                    for ($cp = $p; $cp < $p + $rowspan; $cp++) {
                        if (!isset($scheduleMatrix[$cp][$nextDateKey]) || !$isSameContent($scheduleMatrix[$cp][$nextDateKey], $current)) {
                            $canMerge = false;
                            break;
                        }
                    }
                    if ($canMerge) $colspan++; else break;
                }

                // 3. Đánh dấu các ô "con" đã bị gộp (bỏ qua ô "gốc")
                for ($i = 0; $i < $rowspan; $i++) {
                    for ($j = 0; $j < $colspan; $j++) {
                        if ($i === 0 && $j === 0) continue; // Không đánh dấu ô gốc
                        $assigned[$p + $i][$dates[$index + $j]->toDateString()] = true;
                    }
                }

                // Lưu lại ô gốc kèm rowspan và colspan
                $renderRows[$p][$dateKey] = [
                    'type' => 'subject',
                    'colspan' => $colspan,
                    'rowspan' => $rowspan,
                    'data' => $current
                ];
            }
        }

        return view('schedule::semester', compact('plan', 'renderRows', 'dates', 'periods', 'className'));
    }

    private function parsePeriodRange($range) {
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', trim($range), $m)) return range((int)$m[1], (int)$m[2]);
        return (strtolower($range) === 'chiều') ? range(6, 9) : range(1, 5);
    }
}
