<?php

namespace Modules\Schedule\Application\Shared;

use Modules\Schedule\Models\ScheduleSlot;

/**
 * Cac ham tao nhan hien thi cua ScheduleSlot dung chung cho bao cao xuat Excel va man hinh xem lich.
 */
final class ScheduleSlotLabels
{
    public static function lessonText(ScheduleSlot $slot): string
    {
        $lesson = $slot->subjectLesson;
        if ($lesson === null) {
            return trim((string) ($slot->content ?? ''));
        }

        $title = trim((string) $lesson->title);
        if ($title === '') {
            return 'Bài ' . $lesson->lesson_no;
        }

        // Tieu de da co san tien to dang "Bài 3:", "Unit 1:" thi khong them "Bài n:" nua.
        if (preg_match('/^\p{L}+\s*\d+\s*[:.\-–]/u', $title) === 1) {
            return $title;
        }

        return 'Bài ' . $lesson->lesson_no . ': ' . $title;
    }

    public static function classLabel(ScheduleSlot $slot): string
    {
        $class = $slot->trainingClass;

        return (string) ($class?->code ?: $class?->name ?: '');
    }

    public static function roomLabel(mixed $room): string
    {
        return $room === null ? '' : (string) ($room->code ?: $room->name ?: '');
    }
}
