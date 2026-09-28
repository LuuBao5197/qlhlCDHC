<?php

namespace Modules\Schedule\Application\ExportDepartmentSchedule;

use Carbon\CarbonImmutable;

final class DepartmentScheduleRow
{
    public function __construct(
        public readonly CarbonImmutable $date,
        public readonly string $classes,
        public readonly int $fromPeriod,
        public readonly int $toPeriod,
        public readonly string $room,
        public readonly string $subject,
        public readonly string $lesson,
        public readonly string $teacher,
        public readonly string $note,
    ) {}
}
