<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PdsExportService
{
    /**
     * Maps PDS sheet name -> internal XLSX worksheet filename.
     * These are fixed by the template and must not change.
     */
    private const SHEET_FILE_MAP = [
        'C1' => 'xl/worksheets/sheet1.xml',
        'C2' => 'xl/worksheets/sheet2.xml',
        'C3' => 'xl/worksheets/sheet3.xml',
        'C4' => 'xl/worksheets/sheet4.xml',
    ];

    public function generate(array $pdsData): string
    {
        $path = storage_path('app/templates/pds_template.xlsx');
        if (! file_exists($path)) {
            throw new Exception('TEMPLATE NOT FOUND AT: '.$path);
        }
        Log::info('Template found at: '.$path);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        if (! is_writable($tempDir)) {
            throw new Exception('TEMP DIR NOT WRITABLE: '.$tempDir);
        }

        if (! File::exists($path)) {
            throw new Exception('Missing PDS template file at storage/app/templates/pds_template.xlsx');
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (Exception $e) {
            Log::error('IOFactory load failed: '.$e->getMessage());
            throw $e;
        }

        Log::info('Template loaded. Sheets: '.implode(', ', $spreadsheet->getSheetNames()));

        $this->sanitizeDefinedNames($spreadsheet);

        foreach ($spreadsheet->getDefinedNames() as $definedName) {
            $spreadsheet->removeDefinedName(
                $definedName->getName(),
                $definedName->getWorksheet()
            );
        }

        $c1 = Arr::get($pdsData, 'c1_data', []);
        $c2 = Arr::get($pdsData, 'c2_data', []);
        $c3 = Arr::get($pdsData, 'c3_data', []);
        $c4 = Arr::get($pdsData, 'c4_data', []);
        $signatureDate = Arr::get($c4, 'signature_date');

        $this->fillC1($spreadsheet->getSheetByName('C1'), $c1, $signatureDate);
        $this->fillC2($spreadsheet->getSheetByName('C2'), $c2, $signatureDate);
        $this->fillC3($spreadsheet->getSheetByName('C3'), $c3, $signatureDate);
        $this->fillC4($spreadsheet->getSheetByName('C4'), $c4);

        // Open exported file on C1 for a professional first view.
        $c1Sheet = $spreadsheet->getSheetByName('C1');
        if ($c1Sheet !== null) {
            $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($c1Sheet));
        }

        $filePath = $tempDir . DIRECTORY_SEPARATOR . 'pds_' . uniqid('', true) . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filePath);

        $flat = array_merge(
            Arr::get($pdsData, 'c1_data', []),
            Arr::get($pdsData, 'c2_data', []),
            Arr::get($pdsData, 'c3_data', []),
            Arr::get($pdsData, 'c4_data', [])
        );
        $safeMode = filter_var((string) env('PDS_EXPORT_SAFE_MODE', 'true'), FILTER_VALIDATE_BOOLEAN);
        if ($safeMode) {
            Log::info('PDS export safe mode enabled: skipping checkbox XML mutations for compatibility.');
        } else {
            $this->applyCheckboxes($filePath, $flat);
        }

        if (! file_exists($filePath)) {
            throw new Exception('Temp file was not created at: '.$filePath);
        }

        Log::info('Temp file created: '.$filePath.' Size: '.filesize($filePath).' bytes');

        return $filePath;
    }

    /**
     * Build final XLSX by preserving all template files and replacing only
     * sharedStrings + sheetData blocks from PhpSpreadsheet output.
     */
    private function buildOutput(string $templatePath, string $psFile, string $outputPath): void
    {
        $tmplZip = new \ZipArchive;
        $psZip = new \ZipArchive;

        if ($tmplZip->open($templatePath) !== true) {
            throw new Exception('Cannot open template ZIP: '.$templatePath);
        }
        if ($psZip->open($psFile) !== true) {
            $tmplZip->close();
            throw new Exception('Cannot open PhpSpreadsheet staging ZIP: '.$psFile);
        }

        $files = [];
        for ($i = 0; $i < $tmplZip->numFiles; $i++) {
            $name = $tmplZip->getNameIndex($i);
            if ($name !== false) {
                $files[$name] = $tmplZip->getFromName($name);
            }
        }

        $psSharedStrings = $psZip->getFromName('xl/sharedStrings.xml');
        if ($psSharedStrings !== false) {
            $files['xl/sharedStrings.xml'] = $psSharedStrings;
        }

        // Keep style IDs aligned with PhpSpreadsheet sheetData.
        // If sheetData contains style indexes not present in template styles.xml,
        // Excel repairs "Cell information" records on open.
        $psStyles = $psZip->getFromName('xl/styles.xml');
        if ($psStyles !== false) {
            $files['xl/styles.xml'] = $psStyles;
        }

        foreach (self::SHEET_FILE_MAP as $sheetName => $sheetFile) {
            $tmplSheetXml = $tmplZip->getFromName($sheetFile);
            $psSheetXml = $psZip->getFromName($sheetFile);

            if ($tmplSheetXml === false || $psSheetXml === false) {
                Log::warning("PDS buildOutput: sheet file not found for {$sheetName} ({$sheetFile})");

                continue;
            }

            if (! preg_match('/(<sheetData\b.*?<\/sheetData>)/s', $psSheetXml, $m)) {
                Log::warning("PDS buildOutput: no <sheetData> in PhpSpreadsheet output for {$sheetName}");

                continue;
            }

            $sheetData = $m[1];
            // Remove x14ac-prefixed attrs from rows/cells to prevent invalid XML
            // when template sheets do not declare the x14ac namespace.
            $sheetData = preg_replace('/\s+x14ac:[\w:-]+="[^"]*"/', '', $sheetData) ?? $sheetData;

            $merged = preg_replace('/(<sheetData\b.*?<\/sheetData>)/s', $sheetData, $tmplSheetXml);
            if ($merged !== null) {
                $files[$sheetFile] = $merged;
            }
        }

        $tmplZip->close();
        $psZip->close();

        $outZip = new \ZipArchive;
        $flags = \ZipArchive::CREATE | \ZipArchive::OVERWRITE;
        if ($outZip->open($outputPath, $flags) !== true) {
            throw new Exception('Cannot create output ZIP: '.$outputPath);
        }
        foreach ($files as $name => $content) {
            if ($content !== false) {
                $outZip->addFromString($name, $content);
            }
        }
        $outZip->close();
    }

    // =========================================================================
    // CELL FILLING
    // =========================================================================

    private function fillC1(?Worksheet $sheet, array $data, ?string $signatureDate = null): void
    {
        if (! $sheet) {
            return;
        }

        $this->cleanupC1TemplateArtifacts($sheet);

        $map = [
            'surname' => 'D10',
            'first_name' => 'D11',
            'name_extension' => 'L11',
            'middle_name' => 'D12',
            'place_of_birth' => 'D15',
            'dual_country' => 'L15',
            'height' => 'D22',
            'weight' => 'D24',
            'blood_type' => 'D25',
            'umid_id' => 'D27',
            'pagibig_id' => 'D29',
            'philhealth_no' => 'D31',
            'philsys_no' => 'D32',
            'tin_no' => 'D33',
            'agency_employee_no' => 'D34',
            'telephone_no' => 'I32',
            'mobile_no' => 'I33',
            'email' => 'I34',
            'spouse_surname' => 'D36',
            'spouse_firstname' => 'D37',
            'spouse_extension' => 'G37',
            'spouse_middlename' => 'D38',
            'spouse_occupation' => 'D39',
            'spouse_employer' => 'D40',
            'spouse_business_addr' => 'D41',
            'spouse_telephone' => 'D42',
            'father_surname' => 'D43',
            'father_firstname' => 'D44',
            'father_extension' => 'G44',
            'father_middlename' => 'D45',
            'mother_surname' => 'D47',
            'mother_firstname' => 'D48',
            'mother_middlename' => 'D49',
        ];

        foreach ($map as $key => $cell) {
            $this->writeInput($sheet, $cell, Arr::get($data, $key));
        }

        // Keep first name in a regular, non-merged cell block for consistent layout.
        if ($sheet->getCell('D11')->isInMergeRange()) {
            $sheet->unmergeCells($sheet->getCell('D11')->getMergeRange());
        }
        $sheet->getStyle('D11')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Surname cell is merged and should match template behavior (not hard-centered).
        $sheet->getStyle('D10')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('D11')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Lastname cell is merged and should match template behavior (not hard-centered).
        $sheet->getStyle('D10')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('D12')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $this->writeInput($sheet, 'D13', $this->formatDateForDisplay(Arr::get($data, 'date_of_birth')));
        $sheet->getStyle('D24')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Ensure C1 explicitly labels this section as mother's maiden name.
        $sheet->getCell('B46')->setValue("MOTHER'S MAIDEN NAME");

        // Address fields: write only non-empty values so placeholder labels remain when blank.
        $this->writeInput($sheet, 'I17', Arr::get($data, 'res_house_no'));
        $this->writeInput($sheet, 'L17', Arr::get($data, 'res_street'));
        $this->writeInput($sheet, 'I19', Arr::get($data, 'res_subdivision'));
        $this->writeInput($sheet, 'L19', Arr::get($data, 'res_barangay'));
        $this->writeInput($sheet, 'I22', Arr::get($data, 'res_city'));
        $this->writeInput($sheet, 'L22', Arr::get($data, 'res_province'));
        $this->writeInput($sheet, 'I24', Arr::get($data, 'res_zipcode'));

        $this->writeInput($sheet, 'I25', Arr::get($data, 'perm_house_no'));
        $this->writeInput($sheet, 'L25', Arr::get($data, 'perm_street'));
        $this->writeInput($sheet, 'I27', Arr::get($data, 'perm_subdivision'));
        $this->writeInput($sheet, 'L27', Arr::get($data, 'perm_barangay'));
        $this->writeInput($sheet, 'I29', Arr::get($data, 'perm_city'));
        $this->writeInput($sheet, 'L29', Arr::get($data, 'perm_province'));
        $this->writeInput($sheet, 'I31', Arr::get($data, 'perm_zipcode'));

        // Normalize permanent city/province row to match residential merged answer layout.
        if (!$sheet->getCell('I29')->isInMergeRange()) {
            $sheet->mergeCells('I29:K29');
        }
        if (!$sheet->getCell('L29')->isInMergeRange()) {
            $sheet->mergeCells('L29:N29');
        }

        // Permanent City/Province should render like regular answers (non-italic, centered).
        $sheet->getStyle('I29')->getFont()->setItalic(false);
        $sheet->getStyle('L29')->getFont()->setItalic(false);
        $sheet->getStyle('I29')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('L29')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('I29:K29')->getFont()
            ->setName('Arial Narrow')
            ->setSize(8)
            ->setItalic(false);
        $sheet->getStyle('L29:N29')->getFont()
            ->setName('Arial Narrow')
            ->setSize(8)
            ->setItalic(false);

        $childNameCells = ['I37', 'I38', 'I39', 'I40', 'I41', 'I42', 'I43', 'I44', 'I45', 'I46'];
        $childDobCells = ['M37', 'M38', 'M39', 'M40', 'M41', 'M42', 'M43', 'M44', 'M45', 'M46'];
        foreach (Arr::get($data, 'children', []) as $index => $child) {
            if ($index >= count($childNameCells)) {
                break;
            }
            $this->writeInput($sheet, $childNameCells[$index], Arr::get($child, 'name'));
            $this->writeInput($sheet, $childDobCells[$index], Arr::get($child, 'date_of_birth'));
        }

        $educationRows = ['elementary' => 54, 'secondary' => 55, 'vocational' => 56, 'college' => 57, 'graduate' => 58];
        foreach ($educationRows as $level => $row) {
            $education = Arr::get($data, "education.{$level}", []);
            $this->writeInput($sheet, "D{$row}", Arr::get($education, 'school_name'));
            $this->writeInput($sheet, "G{$row}", Arr::get($education, 'degree'));
            $this->writeInput($sheet, "J{$row}", $this->extractYear(Arr::get($education, 'from')));
            $this->writeInput($sheet, "K{$row}", $this->extractYear(Arr::get($education, 'to')));
            $this->writeInput($sheet, "L{$row}", Arr::get($education, 'units'));
            $this->writeInput($sheet, "M{$row}", $this->extractYear(Arr::get($education, 'year_graduated')));
            $this->writeInput($sheet, "N{$row}", Arr::get($education, 'honors'));

            foreach (['D', 'G', 'J', 'K', 'L', 'M', 'N'] as $column) {
                $sheet->getStyle($column . $row)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
            }
        }

        $this->writeInput($sheet, 'L60', $signatureDate);
    }

    private function cleanupC1TemplateArtifacts(Worksheet $sheet): void
    {
        // C1 template ships with helper/lookup content at the far right.
        // Hide these columns and clear helper values so the list never shows in exports.
        foreach (['O', 'P', 'Q'] as $column) {
            $sheet->getColumnDimension($column)->setVisible(false);
        }

        for ($row = 1; $row <= 350; $row++) {
            $cell = 'Q' . $row;
            $sheet->getCell($cell)->setValue(null);
        }
    }

    private function fillC2(?Worksheet $sheet, array $data, ?string $signatureDate = null): void
    {
        if (! $sheet) {
            return;
        }

        foreach (Arr::get($data, 'eligibility', []) as $index => $item) {
            if ($index > 6) {
                break;
            }
            $row = 5 + $index;
            $this->writeInput($sheet, "A{$row}", Arr::get($item, 'name'));
            $this->writeInput($sheet, "F{$row}", Arr::get($item, 'rating'));
            $this->writeInput($sheet, "G{$row}", Arr::get($item, 'exam_date'));
            $this->writeInput($sheet, "I{$row}", Arr::get($item, 'exam_place'));
            $this->writeInput($sheet, "J{$row}", Arr::get($item, 'license_number'));
            $this->writeInput($sheet, "K{$row}", Arr::get($item, 'license_validity'));
        }

        foreach (Arr::get($data, 'work_experience', []) as $index => $item) {
            if ($index > 27) {
                break;
            }
            $row = 18 + $index;
            $this->writeInput($sheet, "A{$row}", Arr::get($item, 'date_from'));
            $this->writeInput($sheet, "C{$row}", Arr::get($item, 'date_to'));
            $this->writeInput($sheet, "D{$row}", Arr::get($item, 'position_title'));
            $this->writeInput($sheet, "G{$row}", Arr::get($item, 'dept_agency'));
            $this->writeInput($sheet, "J{$row}", Arr::get($item, 'status_appointment'));
            $this->writeInput($sheet, "K{$row}", Arr::get($item, 'govt_service'));
        }

        $this->writeInput($sheet, 'I47', $signatureDate);
    }

    private function fillC3(?Worksheet $sheet, array $data, ?string $signatureDate = null): void
    {
        if (! $sheet) {
            return;
        }

        foreach (Arr::get($data, 'voluntary_work', []) as $index => $item) {
            if ($index > 6) {
                break;
            }
            $row = 6 + $index;
            $this->writeInput($sheet, "A{$row}", Arr::get($item, 'organization'));
            $this->writeInput($sheet, "E{$row}", Arr::get($item, 'date_from'));
            $this->writeInput($sheet, "F{$row}", Arr::get($item, 'date_to'));
            $this->writeInput($sheet, "G{$row}", Arr::get($item, 'hours'));
            $this->writeInput($sheet, "H{$row}", Arr::get($item, 'position'));
        }

        $learningRows = [18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 35, 36, 37, 38];
        foreach (Arr::get($data, 'learning_development', []) as $index => $item) {
            if ($index >= count($learningRows)) {
                break;
            }
            $row = $learningRows[$index];
            $this->writeInput($sheet, "A{$row}", Arr::get($item, 'title'));
            $this->writeInput($sheet, "E{$row}", Arr::get($item, 'date_from'));
            $this->writeInput($sheet, "F{$row}", Arr::get($item, 'date_to'));
            $this->writeInput($sheet, "G{$row}", Arr::get($item, 'hours'));
            $this->writeInput($sheet, "H{$row}", Arr::get($item, 'type'));
            $this->writeInput($sheet, "I{$row}", Arr::get($item, 'conducted_by'));
        }

        foreach (Arr::get($data, 'special_skills', []) as $index => $value) {
            if ($index > 6) {
                break;
            }
            $this->writeInput($sheet, 'A'.(42 + $index), $value);
        }
        foreach (Arr::get($data, 'recognitions', []) as $index => $value) {
            if ($index > 6) {
                break;
            }
            $this->writeInput($sheet, 'C'.(42 + $index), $value);
        }
        foreach (Arr::get($data, 'memberships', []) as $index => $value) {
            if ($index > 6) {
                break;
            }
            $this->writeInput($sheet, 'I'.(42 + $index), $value);
        }

        $this->writeInput($sheet, 'I50', $signatureDate);
    }

    private function fillC4(?Worksheet $sheet, array $data): void
    {
        if (! $sheet) {
            return;
        }

        $map = [
            'q34_details' => 'G10',
            'q35_details' => 'G14',
            'q36_details' => 'G24',
            'q37_details' => 'G28',
            'q38a_details' => 'G32',
            'q38b_details' => 'G35',
            'q39_details' => 'G38',
            'q40a_details' => 'G44',
            'q40b_details' => 'G46',
            'q41_details' => 'G48',
            'govt_id_type' => 'D61',
            'govt_id_no' => 'D62',
            'signature_date' => 'F65',
        ];

        foreach ($map as $key => $cell) {
            $this->writeInput($sheet, $cell, Arr::get($data, $key));
        }

        foreach (Arr::get($data, 'references', []) as $index => $reference) {
            if ($index > 2) {
                break;
            }
            $row = 52 + $index;
            $this->writeInput($sheet, "A{$row}", Arr::get($reference, 'name'));
            $this->writeInput($sheet, "F{$row}", Arr::get($reference, 'address'));
            $this->writeInput($sheet, "G{$row}", Arr::get($reference, 'tel_no'));
        }

        $dateAndPlace = trim(implode(' ', array_filter([
            Arr::get($data, 'id_issue_date'),
            Arr::get($data, 'id_issue_place'),
        ])));
        $this->writeInput($sheet, 'D64', $dateAndPlace);
    }

    private function writeInput(Worksheet $sheet, string $cell, mixed $value): void
    {
        if ($value !== null && trim((string) $value) !== '') {
            $clean = $this->sanitizeCellValue($value);
            if (is_scalar($clean)) {
                $sheet->getCell($cell)->setValueExplicit((string) $clean, DataType::TYPE_STRING);
            } else {
                $sheet->getCell($cell)->setValue($clean);
            }
        }
        $sheet->getStyle($cell)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(false);
    }

    private function formatDateForDisplay(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $ts = strtotime((string) $value);
        if ($ts === false) {
            return (string) $value;
        }

        return date('d/m/Y', $ts);
    }

    private function extractYear(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $raw = trim((string) $value);
        if (preg_match('/^\d{4}$/', $raw) === 1) {
            return $raw;
        }

        if (is_numeric($value)) {
            $numeric = (int) $value;
            if ($numeric >= 1900 && $numeric <= 2999) {
                return (string) $numeric;
            }
        }

        $ts = strtotime($raw);
        if ($ts !== false) {
            return date('Y', $ts);
        }

        return $raw;
    }

    private function sanitizeDefinedNames(Spreadsheet $spreadsheet): int
    {
        $removed = 0;
        foreach ($spreadsheet->getDefinedNames() as $definedName) {
            $value = (string) $definedName->getValue();
            if (str_contains($value, '#REF!') || str_starts_with($value, "''")) {
                $spreadsheet->removeDefinedName($definedName->getName(), $definedName->getWorksheet());
                $removed++;
            }
        }

        return $removed;
    }

    // =========================================================================
    // REPAIR WORKBOOK
    // PhpSpreadsheet drops <controls> and <legacyDrawing> when it rewrites
    // sheet1.xml / sheet4.xml.  Without them Excel ignores checked="1" in
    // ctrlProp files and all checkboxes appear empty.
    // Fix: restore those blocks from the original template.
    // =========================================================================
    private function repairWorkbook(string $filePath, string $templatePath): void
    {
        $tmplZip = new \ZipArchive;
        if ($tmplZip->open($templatePath) !== true) {
            throw new Exception('Cannot open template for repair: '.$templatePath);
        }
        $tmplSheet1 = $tmplZip->getFromName('xl/worksheets/sheet1.xml') ?: '';
        $tmplSheet4 = $tmplZip->getFromName('xl/worksheets/sheet4.xml') ?: '';
        $tmplRels4 = $tmplZip->getFromName('xl/worksheets/_rels/sheet4.xml.rels') ?: '';
        $tmplZip->close();

        $controls1 = '';
        $controls4 = '';
        if (preg_match('/(<controls\b.*?<\/controls>)/s', $tmplSheet1, $m)) {
            $controls1 = $m[1];
        }
        if (preg_match('/(<controls\b.*?<\/controls>)/s', $tmplSheet4, $m)) {
            $controls4 = $m[1];
        }

        $legacyDrawing1 = '';
        $legacyDrawing4 = '';
        if (preg_match('/(<legacyDrawing\b[^>]*\/>)/i', $tmplSheet1, $m)) {
            $legacyDrawing1 = $m[1];
        }
        if (preg_match('/(<legacyDrawing\b[^>]*\/>)/i', $tmplSheet4, $m)) {
            $legacyDrawing4 = $m[1];
        }

        Log::info('repairWorkbook extracted', [
            'c1_len' => strlen($controls1), 'c4_len' => strlen($controls4),
            'ld1' => $legacyDrawing1, 'ld4' => $legacyDrawing4,
        ]);

        $zip = new \ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new Exception('Cannot open for repair: '.$filePath);
        }

        $workbook = $zip->getFromName('xl/workbook.xml');
        if ($workbook !== false) {
            $workbook = preg_replace('/<definedNames>.*?<\/definedNames>/s', '', $workbook) ?? $workbook;
            $zip->addFromString('xl/workbook.xml', $workbook);
        }

        $sheet1 = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheet1 !== false) {
            $sheet1 = preg_replace('/<hyperlinks>.*?<\/hyperlinks>/s', '', $sheet1) ?? $sheet1;
            $sheet1 = preg_replace('/\sshowObjects="[^"]*"/i', '', $sheet1) ?? $sheet1;
            $sheet1 = preg_replace('/<sheetView\b/i', '<sheetView showObjects="all"', $sheet1, 1) ?? $sheet1;
            if ($legacyDrawing1 !== '' && ! str_contains($sheet1, '<legacyDrawing')) {
                $sheet1 = str_replace('</worksheet>', $legacyDrawing1.'</worksheet>', $sheet1);
            }
            if ($controls1 !== '' && ! str_contains($sheet1, '<controls')) {
                $sheet1 = str_replace('</worksheet>', $controls1.'</worksheet>', $sheet1);
            }
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1);
        }

        $sheet4 = $zip->getFromName('xl/worksheets/sheet4.xml');
        if ($sheet4 !== false) {
            $sheet4 = preg_replace('/\sshowObjects="[^"]*"/i', '', $sheet4) ?? $sheet4;
            $sheet4 = preg_replace('/<sheetView\b/i', '<sheetView showObjects="all"', $sheet4, 1) ?? $sheet4;
            if ($legacyDrawing4 !== '' && ! str_contains($sheet4, '<legacyDrawing')) {
                $sheet4 = str_replace('</worksheet>', $legacyDrawing4.'</worksheet>', $sheet4);
            }
            if ($controls4 !== '' && ! str_contains($sheet4, '<controls')) {
                $sheet4 = str_replace('</worksheet>', $controls4.'</worksheet>', $sheet4);
            }
            $zip->addFromString('xl/worksheets/sheet4.xml', $sheet4);
        }

        $sheet1Rels = $zip->getFromName('xl/worksheets/_rels/sheet1.xml.rels');
        if ($sheet1Rels !== false) {
            $sheet1Rels = preg_replace(
                '/\s*<Relationship\b[^>]*relationships\/hyperlink[^>]*\/>/i',
                '',
                $sheet1Rels
            ) ?? $sheet1Rels;
            $sheet1Rels = $this->deduplicateRelationships($sheet1Rels);
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $sheet1Rels);
        }

        if ($tmplRels4 !== '') {
            $zip->addFromString('xl/worksheets/_rels/sheet4.xml.rels', $tmplRels4);
        }

        $zip->close();
        Log::info('repairWorkbook done', ['file' => $filePath]);
    }

    // =========================================================================
    // APPLY CHECKBOXES
    // =========================================================================

    private function applyCheckboxes(string $filePath, array $data): void
    {
        $sex = strtolower(trim((string) Arr::get($data, 'sex', '')));
        $civilStatus = strtolower(trim((string) Arr::get($data, 'civil_status', '')));
        $citizenship = strtolower(trim((string) Arr::get($data, 'citizenship', '')));
        $dualType = strtolower(trim((string) Arr::get($data, 'dual_citizenship_type', '')));
        $q34a = strtolower(trim((string) Arr::get($data, 'q34a', Arr::get($data, 'q34', ''))));
        $q34b = strtolower(trim((string) Arr::get($data, 'q34b', Arr::get($data, 'q34', ''))));
        $q35a = strtolower(trim((string) Arr::get($data, 'q35a', Arr::get($data, 'q35', ''))));
        $q35b = strtolower(trim((string) Arr::get($data, 'q35b', Arr::get($data, 'q35', ''))));
        $q36 = strtolower(trim((string) Arr::get($data, 'q36', '')));
        $q37 = strtolower(trim((string) Arr::get($data, 'q37', '')));
        $q38a = strtolower(trim((string) Arr::get($data, 'q38a', '')));
        $q38b = strtolower(trim((string) Arr::get($data, 'q38b', '')));
        $q39 = strtolower(trim((string) Arr::get($data, 'q39', '')));
        $q40a = strtolower(trim((string) Arr::get($data, 'q40a', '')));
        $q40b = strtolower(trim((string) Arr::get($data, 'q40b', '')));
        $q40c = strtolower(trim((string) Arr::get($data, 'q40c', Arr::get($data, 'q41', ''))));

        Log::info('PDS applyCheckboxes', [
            'sex' => $sex, 'civil' => $civilStatus, 'citizenship' => $citizenship,
        ]);

        $ctrlPropMap = [
            'xl/ctrlProps/ctrlProp4.xml' => $sex === 'male',
            'xl/ctrlProps/ctrlProp5.xml' => $sex === 'female',
            'xl/ctrlProps/ctrlProp6.xml' => $civilStatus === 'single',
            'xl/ctrlProps/ctrlProp7.xml' => $civilStatus === 'married',
            'xl/ctrlProps/ctrlProp8.xml' => $civilStatus === 'widowed',
            'xl/ctrlProps/ctrlProp9.xml' => in_array($civilStatus, ['other/s', 'others'], true),
            'xl/ctrlProps/ctrlProp10.xml' => $civilStatus === 'separated',
            'xl/ctrlProps/ctrlProp2.xml' => $citizenship === 'filipino',
            'xl/ctrlProps/ctrlProp3.xml' => $citizenship === 'dual citizenship',
            'xl/ctrlProps/ctrlProp11.xml' => $dualType === 'by birth',
            'xl/ctrlProps/ctrlProp12.xml' => $dualType === 'by naturalization',
            'xl/ctrlProps/ctrlProp13.xml' => $q34a === 'yes',
            'xl/ctrlProps/ctrlProp14.xml' => $q34a === 'no',
            'xl/ctrlProps/ctrlProp15.xml' => $q34b === 'yes',
            'xl/ctrlProps/ctrlProp16.xml' => $q34b === 'no',
            'xl/ctrlProps/ctrlProp17.xml' => $q35a === 'yes',
            'xl/ctrlProps/ctrlProp18.xml' => $q35a === 'no',
            'xl/ctrlProps/ctrlProp19.xml' => $q35b === 'yes',
            'xl/ctrlProps/ctrlProp20.xml' => $q35b === 'no',
            'xl/ctrlProps/ctrlProp21.xml' => $q36 === 'yes',
            'xl/ctrlProps/ctrlProp22.xml' => $q36 === 'no',
            'xl/ctrlProps/ctrlProp23.xml' => $q37 === 'yes',
            'xl/ctrlProps/ctrlProp24.xml' => $q37 === 'no',
            'xl/ctrlProps/ctrlProp34.xml' => $q38a === 'yes',
            'xl/ctrlProps/ctrlProp35.xml' => $q38a === 'no',
            'xl/ctrlProps/ctrlProp36.xml' => $q38b === 'yes',
            'xl/ctrlProps/ctrlProp37.xml' => $q38b === 'no',
            'xl/ctrlProps/ctrlProp25.xml' => $q39 === 'yes',
            'xl/ctrlProps/ctrlProp26.xml' => $q39 === 'no',
            'xl/ctrlProps/ctrlProp27.xml' => $q40a === 'yes',
            'xl/ctrlProps/ctrlProp30.xml' => $q40a === 'no',
            'xl/ctrlProps/ctrlProp28.xml' => $q40b === 'yes',
            'xl/ctrlProps/ctrlProp31.xml' => $q40b === 'no',
            'xl/ctrlProps/ctrlProp29.xml' => $q40c === 'yes',
            'xl/ctrlProps/ctrlProp32.xml' => $q40c === 'no',
        ];

        $zip = new \ZipArchive;
        if ($zip->open($filePath) !== true) {
            throw new Exception('Cannot open for checkboxes: '.$filePath);
        }

        // Leave drawing/VML files untouched to avoid malformed-shape repairs.

        foreach ($ctrlPropMap as $file => $shouldCheck) {
            $content = $zip->getFromName($file);
            if ($content === false) {
                continue;
            }
            // Strip existing checked attribute via str_replace (avoids regex escaping)
            $content = str_replace(' checked="1"', '', $content);
            $content = str_replace(' checked="Checked"', '', $content);
            if ($shouldCheck) {
                // Insert before the LAST /> = formControlPr closing tag
                $closePos = strrpos($content, '/>');
                if ($closePos !== false) {
                    $content = substr($content, 0, $closePos).' checked="1"/>'.substr($content, $closePos + 2);
                }
            }
            $zip->addFromString($file, $content);
        }

        $zip->close();
    }

    private function applyWorksheetControlChecks(
        \ZipArchive $zip, string $sheetPath, string $relsPath, array $ctrlPropMap
    ): void {
        $sheetXml = $zip->getFromName($sheetPath);
        $relsXml = $zip->getFromName($relsPath);
        if ($sheetXml === false || $relsXml === false) {
            return;
        }

        preg_match_all(
            '/<Relationship\b[^>]*\bId="([^"]+)"[^>]*\bTarget="([^"]+)"[^>]*\/>/i',
            $relsXml, $relMatches, PREG_SET_ORDER
        );
        $ridToCtrlProp = [];
        foreach ($relMatches as $match) {
            $target = $match[2] ?? '';
            if ($target === '' || stripos($target, 'ctrlProp') === false) {
                continue;
            }
            $target = preg_replace('/^\.\.\//', 'xl/', $target) ?? $target;
            $ridToCtrlProp[$match[1]] = $target;
        }
        if ($ridToCtrlProp === []) {
            return;
        }

        $sheetXml = preg_replace_callback(
            '/(<control\b[^>]*\br:id="([^"]+)"[^>]*>.*?<controlPr\b)([^>]*)(>)/is',
            function (array $matches) use ($ridToCtrlProp, $ctrlPropMap): string {
                $rid = $matches[2] ?? '';
                $ctrlPropPath = $ridToCtrlProp[$rid] ?? null;
                if ($ctrlPropPath === null || ! array_key_exists($ctrlPropPath, $ctrlPropMap)) {
                    return $matches[0];
                }
                $shouldCheck = (bool) $ctrlPropMap[$ctrlPropPath];
                $attrs = str_replace(' checked="Checked"', '', $matches[3] ?? '');
                $attrs = str_replace(' checked="1"', '', $attrs);
                if ($shouldCheck) {
                    $attrs .= ' checked="Checked"';
                }

                return $matches[1].$attrs.$matches[4];
            },
            $sheetXml
        ) ?? $sheetXml;
        $zip->addFromString($sheetPath, $sheetXml);
    }

    private function deduplicateRelationships(string $relsContent): string
    {
        $seen = [];

        return preg_replace_callback(
            '/<Relationship\b[^>]*\/>/i',
            function (array $matches) use (&$seen): string {
                $rel = $matches[0];
                preg_match('/Target="([^"]+)"/i', $rel, $targetMatch);
                $target = $targetMatch[1] ?? $rel;

                if (in_array($target, $seen, true)) {
                    return '';
                }

                $seen[] = $target;

                return $rel;
            },
            $relsContent
        ) ?? $relsContent;
    }

    private function applyVmlChecks(string $vml, array $shapeStates): string
    {
        foreach ($shapeStates as $shapeId => $shouldCheck) {

            $needle = 'id="'.$shapeId.'"';
            $shapePos = strpos($vml, $needle);
            if ($shapePos === false) {
                $needle = 'o:spid="'.$shapeId.'"';
                $shapePos = strpos($vml, $needle);
            }
            if ($shapePos === false) {
                continue;
            }

            $shapeStart = strrpos(substr($vml, 0, $shapePos), '<v:shape ');
            if ($shapeStart === false) {
                continue;
            }
            $shapeEnd = strpos($vml, '</v:shape>', $shapePos);
            if ($shapeEnd === false) {
                continue;
            }

            $shapeBlock = substr($vml, $shapeStart, ($shapeEnd + strlen('</v:shape>')) - $shapeStart);
            $clientStartPos = strpos($shapeBlock, '<x:ClientData');
            if ($clientStartPos === false) {
                continue;
            }
            $clientEndPos = strpos($shapeBlock, '</x:ClientData>', $clientStartPos);
            if ($clientEndPos === false) {
                continue;
            }

            $clientBlock = substr(
                $shapeBlock, $clientStartPos,
                ($clientEndPos + strlen('</x:ClientData>')) - $clientStartPos
            );

            // ---- strip every existing <x:Checked> variant ----
            // Self-closing forms first
            $clientBlock = str_replace('<x:Checked/>', '', $clientBlock);
            $clientBlock = str_replace('<x:Checked />', '', $clientBlock);
            // Paired forms: loop to catch all occurrences
            $openTag = '<x:Checked>';
            $closeTag = '</x:Checked>';
            while (($p = strpos($clientBlock, $openTag)) !== false) {
                $q = strpos($clientBlock, $closeTag, $p);
                if ($q === false) {
                    break;
                }
                $clientBlock = substr($clientBlock, 0, $p)
                    .substr($clientBlock, $q + strlen($closeTag));
            }
            // ---- end strip ----

            if ($shouldCheck) {
                // Insert <x:Checked>1</x:Checked> immediately BEFORE <x:NoThreeD
                // Uses stripos so it handles <x:NoThreeD/> and <x:NoThreeD />
                $noThreeDPos = stripos($clientBlock, '<x:NoThreeD');
                if ($noThreeDPos !== false) {
                    $clientBlock = substr($clientBlock, 0, $noThreeDPos)
                        ."<x:Checked>1</x:Checked>\r\n   "
                        .substr($clientBlock, $noThreeDPos);
                } else {
                    // Fallback: insert before </x:ClientData>
                    $endPos = strpos($clientBlock, '</x:ClientData>');
                    if ($endPos !== false) {
                        $clientBlock = substr($clientBlock, 0, $endPos)
                            ."   <x:Checked>1</x:Checked>\r\n  "
                            .substr($clientBlock, $endPos);
                    }
                }
            }

            $updatedShapeBlock = substr($shapeBlock, 0, $clientStartPos)
                .$clientBlock
                .substr($shapeBlock, $clientEndPos + strlen('</x:ClientData>'));

            $vml = substr($vml, 0, $shapeStart)
                .$updatedShapeBlock
                .substr($vml, $shapeEnd + strlen('</v:shape>'));
        }

        return $vml;
    }

    private function applyWorksheetControlChecks(
        \ZipArchive $zip,
        string $sheetXmlPath,
        string $vml,
        array $shapeStates
    ): void {
        $sheetXml = $zip->getFromName($sheetXmlPath);
        if ($sheetXml === false) {
            return;
        }

        $shapeIdStates = [];
        foreach ($shapeStates as $shapeKey => $shouldCheck) {
            $shapeId = $this->resolveWorksheetShapeId($vml, $shapeKey);
            if ($shapeId !== null) {
                $shapeIdStates[(string) $shapeId] = $shouldCheck;
            }
        }

        if ($shapeIdStates === []) {
            return;
        }

        $sheetXml = preg_replace_callback(
            '/(<control\b[^>]*\bshapeId="(\d+)"[^>]*>\s*<controlPr\b)([^>]*)(>)/i',
            function (array $matches) use ($shapeIdStates) {
                $shapeId = $matches[2];
                if (!array_key_exists($shapeId, $shapeIdStates)) {
                    return $matches[0];
                }

                $attrs = preg_replace('/\schecked="[^"]*"/i', '', $matches[3]) ?? $matches[3];
                if ($shapeIdStates[$shapeId]) {
                    $attrs .= ' checked="1"';
                }

                return $matches[1] . $attrs . $matches[4];
            },
            $sheetXml
        ) ?? $sheetXml;

        $zip->addFromString($sheetXmlPath, $sheetXml);
    }

    private function sanitizeCellValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Keep tab/newline/carriage return, strip other control chars that can corrupt XML parts.
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    }

    private function resolveWorksheetShapeId(string $vml, string $shapeKey): ?int
    {
        if (preg_match('/_x0000_s(\d+)$/i', $shapeKey, $numMatch) === 1) {
            return (int) $numMatch[1];
        }

        $quotedShape = preg_quote($shapeKey, '/');
        if (
            preg_match(
                '/<v:shape\b[^>]*\bid="' . $quotedShape . '"[^>]*\bo:spid="_x0000_s(\d+)"[^>]*>/i',
                $vml,
                $shapeMatch
            ) === 1
        ) {
            return (int) $shapeMatch[1];
        }

        return null;
    }
}
