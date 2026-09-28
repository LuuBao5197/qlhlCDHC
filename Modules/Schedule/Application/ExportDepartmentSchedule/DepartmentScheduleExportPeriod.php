<?php

namespace Modules\Schedule\Application\ExportDepartmentSchedule;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class DepartmentScheduleExportPeriod
{
    public const MODE_MONTH = 'month';

    public const MODE_WEEK = 'week';

    public const MODE_DAY = 'day';

    public const MODES = [self::MODE_MONTH, self::MODE_WEEK, self::MODE_DAY];

    private function __construct(
        public readonly string $mode,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public static function make(string $mode, CarbonImmutable $anchorDate): self
    {
        $date = $anchorDate->startOfDay();

        return match ($mode) {
            self::MODE_MONTH => new self($mode, $date->startOfMonth(), $date->endOfMonth()->startOfDay()),
            self::MODE_WEEK => new self($mode, $date->startOfWeek(CarbonImmutable::MONDAY), $date->endOfWeek(CarbonImmutable::SUNDAY)->startOfDay()),
            self::MODE_DAY => new self($mode, $date, $date),
            default => throw new InvalidArgumentException('Che do xuat khong hop le: ' . $mode),
        };
    }

    /**
     * Dong tieu de thu hai cua mau: "Phân công giảng dạy tháng 9/2026".
     */
    public function title(): string
    {
        return match ($this->mode) {
            self::MODE_MONTH => sprintf('Phân công giảng dạy tháng %d/%d', $this->start->month, $this->start->year),
            self::MODE_WEEK => sprintf('Phân công giảng dạy tuần %d/%d', $this->start->isoWeek, $this->start->isoWeekYear),
            default => 'Phân công giảng dạy ngày ' . $this->start->format('d/m/Y'),
        };
    }

    public function rangeLabel(): string
    {
        return sprintf('(Từ ngày %s đến ngày %s)', $this->start->format('d/m/Y'), $this->end->format('d/m/Y'));
    }

    /**
     * Ten sheet dang "<ma khoa>_T9"; Excel gioi han 31 ky tu va cam \ / ? * [ ] :
     */
    public function sheetTitle(string $departmentCode): string
    {
        $suffix = match ($this->mode) {
            self::MODE_MONTH => 'T' . $this->start->month,
            self::MODE_WEEK => 'Tuan' . $this->start->isoWeek,
            default => $this->start->format('d-m'),
        };

        $code = trim((string) preg_replace('/[\\\\\\/?*\[\]:]/u', '', $departmentCode));
        $title = ($code !== '' ? $code . '_' : '') . $suffix;

        return mb_substr($title, 0, 31, 'UTF-8');
    }

    public function fileSuffix(): string
    {
        return match ($this->mode) {
            self::MODE_MONTH => $this->start->format('Y-m'),
            self::MODE_WEEK => $this->start->isoWeekYear . '-W' . str_pad((string) $this->start->isoWeek, 2, '0', STR_PAD_LEFT),
            default => $this->start->format('Y-m-d'),
        };
    }
}
