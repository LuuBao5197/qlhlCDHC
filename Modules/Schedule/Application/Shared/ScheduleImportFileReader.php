<?php

namespace Modules\Schedule\Application\Shared;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Reads an uploaded schedule import file (.xlsx/.csv/.txt) into a plain
 * array of rows (first row is the header). Shared between the real create
 * import flow and the dry-run preview flow so both parse files identically.
 */
class ScheduleImportFileReader
{
    public function readRows(UploadedFile $file, string $errorKey = 'import_file'): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        return match ($extension) {
            'xlsx' => $this->readXlsxRows($file->getRealPath(), $errorKey),
            default => $this->readCsvRows($file->getRealPath(), $errorKey),
        };
    }

    private function readCsvRows(string $path, string $errorKey): array
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            throw ValidationException::withMessages([
                $errorKey => 'Khong the doc file import.',
            ]);
        }

        $content = $this->toUtf8($content);

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Excel commonly saves Vietnamese CSV/TXT exports as ANSI
     * (Windows-1258/1252) rather than UTF-8. Those raw bytes are invalid
     * UTF-8 and later break json_encode() when the parsed values are
     * returned in an API response, so normalize to UTF-8 up front.
     *
     * mbstring's encoding list has no Vietnamese CP1258, so mb_detect_encoding()
     * would throw on it — iconv supports more codepages and simply fails
     * (returns false) on ones it doesn't recognize instead of throwing.
     */
    private function toUtf8(string $content): string
    {
        if ($content === '' || mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        foreach (['CP1258', 'Windows-1252', 'ISO-8859-1'] as $encoding) {
            $converted = @iconv($encoding, 'UTF-8//TRANSLIT//IGNORE', $content);
            if ($converted !== false && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        return mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
    }

    private function readXlsxRows(string $path, string $errorKey): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                $errorKey => 'Khong the mo file Excel import.',
            ]);
        }

        $sharedStrings = $this->parseXlsxSharedStrings($zip->getFromName('xl/sharedStrings.xml') ?: null);
        $dateStyles = $this->parseXlsxDateStyleIndexes($zip->getFromName('xl/styles.xml') ?: null);
        $sheetPath = $this->firstWorksheetPath($zip);
        $sheetXml = $sheetPath ? $zip->getFromName($sheetPath) : false;
        $zip->close();

        if ($sheetXml === false || $sheetXml === null) {
            throw ValidationException::withMessages([
                $errorKey => 'File Excel import khong co du lieu sheet hop le.',
            ]);
        }

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false || !isset($xml->sheetData)) {
            throw ValidationException::withMessages([
                $errorKey => 'Khong the doc du lieu trong file Excel import.',
            ]);
        }

        $rows = [];

        foreach ($xml->sheetData->row as $rowNode) {
            $row = [];

            foreach ($rowNode->c as $cell) {
                $reference = (string) ($cell['r'] ?? '');
                $columnIndex = $this->columnIndexFromReference($reference);
                $row[$columnIndex] = $this->parseXlsxCellValue($cell, $sharedStrings, $dateStyles);
            }

            if ($row === []) {
                continue;
            }

            ksort($row);
            $normalized = [];
            $maxIndex = max(array_keys($row));
            for ($i = 0; $i <= $maxIndex; $i++) {
                $normalized[] = $row[$i] ?? '';
            }

            $rows[] = $normalized;
        }

        return $rows;
    }

    private function parseXlsxSharedStrings(?string $xml): array
    {
        if ($xml === null || $xml === '') {
            return [];
        }

        $parsed = @simplexml_load_string($xml);
        if ($parsed === false) {
            return [];
        }

        $values = [];
        foreach ($parsed->si as $item) {
            $text = '';

            if (isset($item->t)) {
                $text = (string) $item->t;
            } elseif (isset($item->r)) {
                foreach ($item->r as $run) {
                    $text .= (string) ($run->t ?? '');
                }
            }

            $values[] = $text;
        }

        return $values;
    }

    private function parseXlsxDateStyleIndexes(?string $xml): array
    {
        if ($xml === null || $xml === '') {
            return [];
        }

        $parsed = @simplexml_load_string($xml);
        if ($parsed === false || !isset($parsed->cellXfs)) {
            return [];
        }

        $customFormats = [];
        if (isset($parsed->numFmts)) {
            foreach ($parsed->numFmts->numFmt as $numFmt) {
                $customFormats[(int) $numFmt['numFmtId']] = strtolower((string) $numFmt['formatCode']);
            }
        }

        $dateStyles = [];
        foreach ($parsed->cellXfs->xf as $index => $xf) {
            $numFmtId = (int) ($xf['numFmtId'] ?? 0);
            $formatCode = $customFormats[$numFmtId] ?? null;

            if ($this->isDateNumFmtId($numFmtId, $formatCode)) {
                $dateStyles[(int) $index] = true;
            }
        }

        return $dateStyles;
    }

    private function isDateNumFmtId(int $numFmtId, ?string $formatCode): bool
    {
        if (in_array($numFmtId, [14, 15, 16, 17, 18, 19, 20, 21, 22, 45, 46, 47], true)) {
            return true;
        }

        if ($formatCode === null) {
            return false;
        }

        return preg_match('/[dmyhs]/', $formatCode) === 1;
    }

    private function firstWorksheetPath(\ZipArchive $zip): ?string
    {
        $workbookRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookRelsXml !== false && $workbookRelsXml !== null) {
            $rels = @simplexml_load_string($workbookRelsXml);
            if ($rels !== false) {
                foreach ($rels->Relationship as $relationship) {
                    $target = (string) ($relationship['Target'] ?? '');
                    if (str_contains($target, 'worksheets/')) {
                        return 'xl/' . ltrim($target, '/');
                    }
                }
            }
        }

        return $zip->locateName('xl/worksheets/sheet1.xml') !== false
            ? 'xl/worksheets/sheet1.xml'
            : null;
    }

    private function parseXlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings, array $dateStyles): string
    {
        $type = (string) ($cell['t'] ?? '');
        $styleIndex = isset($cell['s']) ? (int) $cell['s'] : null;
        $value = isset($cell->v) ? (string) $cell->v : '';

        if ($type === 's') {
            return (string) ($sharedStrings[(int) $value] ?? '');
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        if ($type === 'b') {
            return $value === '1' ? '1' : '0';
        }

        if ($styleIndex !== null && isset($dateStyles[$styleIndex]) && is_numeric($value)) {
            return Carbon::create(1899, 12, 30)->addDays((int) floor((float) $value))->toDateString();
        }

        return trim($value);
    }

    private function columnIndexFromReference(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference));
        if ($letters === '') {
            return 0;
        }

        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    public function normalizeHeader(mixed $header): string
    {
        $value = strtolower(trim((string) $header));

        return ltrim($value, "\xEF\xBB\xBF");
    }

    public function parseWeekdaysFromString(string $input): array
    {
        if (empty($input)) {
            return [];
        }

        $parts = preg_split('/[,;]+/', trim($input), -1, PREG_SPLIT_NO_EMPTY);
        $weekdays = [];

        $dayNameMap = [
            'monday' => 2, 'mon' => 2,
            'tuesday' => 3, 'tue' => 3,
            'wednesday' => 4, 'wed' => 4,
            'thursday' => 5, 'thu' => 5,
            'friday' => 6, 'fri' => 6,
            'saturday' => 7, 'sat' => 7,
            'sunday' => 8, 'sun' => 8,
            'chu nhat' => 8,
            'thu hai' => 2,
            'thu ba' => 3,
            'thu tu' => 4,
            'thu nam' => 5,
            'thu sau' => 6,
            'thu bay' => 7,
        ];

        foreach ($parts as $part) {
            $part = trim(strtolower($part));

            if (is_numeric($part)) {
                $dayNum = (int) $part;
                if ($dayNum >= 2 && $dayNum <= 8) {
                    $weekdays[] = $dayNum;
                }
            } elseif (isset($dayNameMap[$part])) {
                $weekdays[] = $dayNameMap[$part];
            }
        }

        return collect($weekdays)->unique()->sort()->values()->all();
    }
}
