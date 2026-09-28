<?php

namespace Modules\Schedule\Application\ExportDepartmentSchedule;

use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Dien du lieu vao file mau report_scheduleOfDepartmentByMonth.xlsx cua khach hang.
 *
 * Bo cuc mau: dong 1-7 dau trang, dong 9 tieu de bang, dong 10..98 du lieu mau,
 * dong 99 ghi chu, dong 101 "CHU NHIEM KHOA", dong 106 nguoi ky.
 * Cac cot K:M (so tiet LT/TH/coi thi) cua mau bi loai bo khi xuat.
 */
class DepartmentScheduleXlsxWriter
{
    private const FIRST_DATA_ROW = 10;

    private const TEMPLATE_LAST_DATA_ROW = 98;

    private const TEMPLATE_SIGNER_ROW = 106;

    private const WEEKDAYS = [
        1 => 'Thứ Hai',
        2 => 'Thứ Ba',
        3 => 'Thứ Tư',
        4 => 'Thứ Năm',
        5 => 'Thứ Sáu',
        6 => 'Thứ Bảy',
        7 => 'Chủ Nhật',
    ];

    public function __construct(private ?string $templatePath = null)
    {
        $this->templatePath ??= base_path('Modules/Schedule/resources/templates/report_scheduleOfDepartmentByMonth.xlsx');
    }

    /**
     * @param array<int, DepartmentScheduleRow> $rows
     */
    public function write(
        array $rows,
        DepartmentScheduleExportPeriod $period,
        string $departmentCode,
        string $departmentName,
        string $documentNumber,
        CarbonImmutable $issuedDate,
        string $signerName,
    ): Spreadsheet {
        $spreadsheet = IOFactory::load($this->templatePath);
        $sheet = $spreadsheet->getSheet(0);
        $sheet->removeColumn('K', 3);

        $this->fillHeader($sheet, $period, $departmentName, $documentNumber, $issuedDate);

        $rowCount = max(count($rows), 1);
        $lastDataRow = self::FIRST_DATA_ROW + $rowCount - 1;
        $delta = $lastDataRow - self::TEMPLATE_LAST_DATA_ROW;
        $signerRow = self::TEMPLATE_SIGNER_ROW + $delta;

        $this->resizeDataArea($sheet, $delta);
        $this->fillRows($sheet, $rows, $lastDataRow);
        $this->fillFooter($sheet, $signerRow, $signerName);

        $sheet->setTitle($period->sheetTitle($departmentCode));
        $sheet->getPageSetup()->setPrintArea('A1:J' . $signerRow);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(9, 9);
        $spreadsheet->setActiveSheetIndex(0);
        $sheet->setSelectedCell('A1');

        return $spreadsheet;
    }

    private function fillHeader(
        Worksheet $sheet,
        DepartmentScheduleExportPeriod $period,
        string $departmentName,
        string $documentNumber,
        CarbonImmutable $issuedDate,
    ): void {
        $sheet->setCellValue('A2', $this->departmentHeading($departmentName));
        $sheet->setCellValue('A3', 'Số: ' . $documentNumber);
        $sheet->setCellValue('H3', sprintf(
            'Thành phố Hồ Chí Minh, ngày %d tháng %d năm %d',
            $issuedDate->day,
            $issuedDate->month,
            $issuedDate->year
        ));
        $sheet->setCellValue('A6', $period->title());
        $sheet->setCellValue('A7', $period->rangeLabel());
    }

    private function departmentHeading(string $departmentName): string
    {
        $name = trim($departmentName);

        // Ten khoa da co san tien to "Khoa ..." (ngoai tru "Khoa học ...") thi khong them "KHOA" nua.
        if (preg_match('/^khoa\s+(?!học)/iu', $name) === 1) {
            return mb_strtoupper($name, 'UTF-8');
        }

        return 'KHOA ' . mb_strtoupper($name, 'UTF-8');
    }

    /**
     * Mau co 89 dong du lieu (10..98); them/bot dong de vua so dong thuc te.
     * Ham insert/remove cua PhpSpreadsheet tu dich cac o gop o chan trang.
     */
    private function resizeDataArea(Worksheet $sheet, int $delta): void
    {
        if ($delta > 0) {
            $sheet->insertNewRowBefore(self::TEMPLATE_LAST_DATA_ROW + 1, $delta);
        } elseif ($delta < 0) {
            $sheet->removeRow(self::FIRST_DATA_ROW + (self::TEMPLATE_LAST_DATA_ROW - self::FIRST_DATA_ROW + 1) + $delta, -$delta);
        }
    }

    /**
     * @param array<int, DepartmentScheduleRow> $rows
     */
    private function fillRows(Worksheet $sheet, array $rows, int $lastDataRow): void
    {
        $dateFormat = $sheet->getStyle('A' . self::FIRST_DATA_ROW)->getNumberFormat()->getFormatCode();

        // Xoa noi dung mau con sot, nhan ban dinh dang tung cot cua dong 10 (duplicateStyle chi lay o dau cua vung).
        for ($r = self::FIRST_DATA_ROW; $r <= $lastDataRow; $r++) {
            foreach (range('A', 'J') as $column) {
                if ($r > self::FIRST_DATA_ROW) {
                    $sheet->duplicateStyle($sheet->getStyle($column . self::FIRST_DATA_ROW), $column . $r);
                }
                $sheet->setCellValue($column . $r, null);
            }
            $sheet->getRowDimension($r)->setRowHeight(-1);
        }

        $r = self::FIRST_DATA_ROW;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $r, ExcelDate::PHPToExcel($row->date->toDateTime()));
            $sheet->getStyle('A' . $r)->getNumberFormat()->setFormatCode($dateFormat);
            $sheet->setCellValue('B' . $r, self::WEEKDAYS[$row->date->dayOfWeekIso]);
            $sheet->setCellValue('C' . $r, $row->classes);
            $sheet->setCellValue('D' . $r, $row->fromPeriod);
            $sheet->setCellValue('E' . $r, $row->toPeriod);
            $sheet->setCellValue('F' . $r, $row->room);
            $sheet->setCellValue('G' . $r, $row->subject);
            $sheet->setCellValue('H' . $r, $row->lesson);
            $sheet->setCellValue('I' . $r, $row->teacher);
            $sheet->setCellValue('J' . $r, $row->note !== '' ? $row->note : null);
            $r++;
        }

        $dataRange = 'A' . self::FIRST_DATA_ROW . ':J' . $lastDataRow;
        $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        foreach (['B', 'C', 'F', 'G', 'H', 'I', 'J'] as $column) {
            $sheet->getStyle($column . self::FIRST_DATA_ROW . ':' . $column . $lastDataRow)
                ->getAlignment()->setWrapText(true);
        }
    }

    private function fillFooter(Worksheet $sheet, int $signerRow, string $signerName): void
    {
        $sheet->setCellValue('H' . $signerRow, $signerName);
    }
}
