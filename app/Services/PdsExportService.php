<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PdsExportService
{
    public function generate(array $pdsData): string
    {
        $path = storage_path('app/templates/pds_template.xlsx');
        if (!file_exists($path)) {
            throw new Exception('TEMPLATE NOT FOUND AT: ' . $path);
        }
        Log::info('Template found at: ' . $path);

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
            Log::info('Created temp dir: ' . $tempDir);
        }
        if (!is_writable($tempDir)) {
            throw new Exception('TEMP DIR NOT WRITABLE: ' . $tempDir);
        }
        Log::info('Temp dir OK: ' . $tempDir);

        if (!File::exists($path)) {
            throw new Exception('Missing PDS template file at storage/app/templates/pds_template.xlsx');
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (Exception $e) {
            Log::error('IOFactory load failed: ' . $e->getMessage());
            throw $e;
        }

        Log::info('Template loaded. Sheets: ' . implode(', ', $spreadsheet->getSheetNames()));

        $removedDefinedNames = $this->sanitizeDefinedNames($spreadsheet);
        if ($removedDefinedNames > 0) {
            Log::info('Removed invalid defined names: ' . $removedDefinedNames);
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

        $filePath = $tempDir . DIRECTORY_SEPARATOR . 'pds_' . uniqid('', true) . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filePath);

        $flat = array_merge(
            Arr::get($pdsData, 'c1_data', []),
            Arr::get($pdsData, 'c2_data', []),
            Arr::get($pdsData, 'c3_data', []),
            Arr::get($pdsData, 'c4_data', [])
        );
        $this->applyCheckboxes($filePath, $flat);

        if (!file_exists($filePath)) {
            throw new Exception('Temp file was not created at: ' . $filePath);
        }

        Log::info('Temp file created: ' . $filePath . ' Size: ' . filesize($filePath) . ' bytes');

        return $filePath;
    }

    private function fillC1(?Worksheet $sheet, array $data, ?string $signatureDate = null): void
    {
        if (!$sheet) {
            return;
        }

        $map = [
            'surname' => 'D10',
            'first_name' => 'D11',
            'name_extension' => 'L11',
            'middle_name' => 'D12',
            'date_of_birth' => 'D13',
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

        $childNameCells = ['I37', 'I38', 'I39', 'I40', 'I41', 'I42', 'I43', 'I44', 'I45', 'I46'];
        $childDobCells = ['M37', 'M38', 'M39', 'M40', 'M41', 'M42', 'M43', 'M44', 'M45', 'M46'];
        foreach (Arr::get($data, 'children', []) as $index => $child) {
            if ($index >= count($childNameCells)) {
                break;
            }

            $this->writeInput($sheet, $childNameCells[$index], Arr::get($child, 'name'));
            $this->writeInput($sheet, $childDobCells[$index], Arr::get($child, 'date_of_birth'));
        }

        $educationRows = [
            'elementary' => 54,
            'secondary' => 55,
            'vocational' => 56,
            'college' => 57,
            'graduate' => 58,
        ];

        foreach ($educationRows as $level => $row) {
            $education = Arr::get($data, "education.{$level}", []);
            $this->writeInput($sheet, "D{$row}", Arr::get($education, 'school_name'));
            $this->writeInput($sheet, "G{$row}", Arr::get($education, 'degree'));
            $this->writeInput($sheet, "J{$row}", Arr::get($education, 'from'));
            $this->writeInput($sheet, "K{$row}", Arr::get($education, 'to'));
            $this->writeInput($sheet, "L{$row}", Arr::get($education, 'units'));
            $this->writeInput($sheet, "M{$row}", Arr::get($education, 'year_graduated'));
            $this->writeInput($sheet, "N{$row}", Arr::get($education, 'honors'));
        }

        $this->writeInput($sheet, 'L60', $signatureDate);
    }

    private function fillC2(?Worksheet $sheet, array $data, ?string $signatureDate = null): void
    {
        if (!$sheet) {
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
        if (!$sheet) {
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
            $row = 42 + $index;
            $this->writeInput($sheet, "A{$row}", $value);
        }

        foreach (Arr::get($data, 'recognitions', []) as $index => $value) {
            if ($index > 6) {
                break;
            }
            $row = 42 + $index;
            $this->writeInput($sheet, "C{$row}", $value);
        }

        foreach (Arr::get($data, 'memberships', []) as $index => $value) {
            if ($index > 6) {
                break;
            }
            $row = 42 + $index;
            $this->writeInput($sheet, "I{$row}", $value);
        }

        $this->writeInput($sheet, 'I50', $signatureDate);
    }

    private function fillC4(?Worksheet $sheet, array $data): void
    {
        if (!$sheet) {
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
            $sheet->getCell($cell)->setValue($value);
        }

        $sheet->getStyle($cell)->getAlignment()
            ->setVertical(Alignment::VERTICAL_BOTTOM)
            ->setWrapText(false);
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

    private function applyCheckboxes(string $filePath, array $data): void
    {
        $sex         = strtolower(trim((string) Arr::get($data, 'sex', '')));
        $civilStatus = strtolower(trim((string) Arr::get($data, 'civil_status', '')));
        $citizenship = strtolower(trim((string) Arr::get($data, 'citizenship', '')));
        $dualType    = strtolower(trim((string) Arr::get($data, 'dual_citizenship_type', '')));
        $q34a = strtolower(trim((string) Arr::get($data, 'q34a', Arr::get($data, 'q34', ''))));
        $q34b = strtolower(trim((string) Arr::get($data, 'q34b', Arr::get($data, 'q34', ''))));
        $q35a = strtolower(trim((string) Arr::get($data, 'q35a', Arr::get($data, 'q35', ''))));
        $q35b = strtolower(trim((string) Arr::get($data, 'q35b', Arr::get($data, 'q35', ''))));
        $q36  = strtolower(trim((string) Arr::get($data, 'q36', '')));
        $q37  = strtolower(trim((string) Arr::get($data, 'q37', '')));
        $q38a = strtolower(trim((string) Arr::get($data, 'q38a', '')));
        $q38b = strtolower(trim((string) Arr::get($data, 'q38b', '')));
        $q39  = strtolower(trim((string) Arr::get($data, 'q39', '')));
        $q40a = strtolower(trim((string) Arr::get($data, 'q40a', '')));
        $q40b = strtolower(trim((string) Arr::get($data, 'q40b', '')));
        $q40c = strtolower(trim((string) Arr::get($data, 'q40c', Arr::get($data, 'q41', ''))));

        $c1States = [
            '_x0000_s1049' => $sex === 'male',
            '_x0000_s1050' => $sex === 'female',
            '_x0000_s1058' => $civilStatus === 'single',
            '_x0000_s1059' => $civilStatus === 'married',
            '_x0000_s1060' => $civilStatus === 'widowed',
            '_x0000_s1061' => in_array($civilStatus, ['other/s', 'others'], true),
            '_x0000_s1062' => $civilStatus === 'separated',
            '_x0000_s1045' => $citizenship === 'filipino',
            '_x0000_s1046' => $citizenship === 'dual citizenship',
            '_x0000_s1063' => $dualType === 'by birth',
            '_x0000_s1064' => $dualType === 'by naturalization',
        ];

        $c4States = [
            'Check_x0020_Box_x0020_1' => $q34a === 'yes',
            'Check_x0020_Box_x0020_2' => $q34a === 'no',
            'Check_x0020_Box_x0020_3' => $q34b === 'yes',
            'Check_x0020_Box_x0020_4' => $q34b === 'no',
            'Check_x0020_Box_x0020_5' => $q35a === 'yes',
            'Check_x0020_Box_x0020_6' => $q35a === 'no',
            'Check_x0020_Box_x0020_7' => $q35b === 'yes',
            'Check_x0020_Box_x0020_8' => $q35b === 'no',
            'Check_x0020_Box_x0020_9' => $q36 === 'yes',
            'Check_x0020_Box_x0020_10' => $q36 === 'no',
            'Check_x0020_Box_x0020_11' => $q37 === 'yes',
            'Check_x0020_Box_x0020_12' => $q37 === 'no',
            'Check_x0020_Box_x0020_26' => $q38a === 'yes',
            'Check_x0020_Box_x0020_27' => $q38a === 'no',
            'Check_x0020_Box_x0020_28' => $q38b === 'yes',
            'Check_x0020_Box_x0020_29' => $q38b === 'no',
            'Check_x0020_Box_x0020_13' => $q39 === 'yes',
            'Check_x0020_Box_x0020_14' => $q39 === 'no',
            'Check_x0020_Box_x0020_15' => $q40a === 'yes',
            'Check_x0020_Box_x0020_18' => $q40a === 'no',
            'Check_x0020_Box_x0020_16' => $q40b === 'yes',
            'Check_x0020_Box_x0020_19' => $q40b === 'no',
            'Check_x0020_Box_x0020_17' => $q40c === 'yes',
            'Check_x0020_Box_x0020_20' => $q40c === 'no',
        ];

        $ctrlPropMap = [
            'xl/ctrlProps/ctrlProp4.xml'  => $sex === 'male',
            'xl/ctrlProps/ctrlProp5.xml'  => $sex === 'female',
            'xl/ctrlProps/ctrlProp6.xml'  => $civilStatus === 'single',
            'xl/ctrlProps/ctrlProp7.xml'  => $civilStatus === 'married',
            'xl/ctrlProps/ctrlProp8.xml'  => $civilStatus === 'widowed',
            'xl/ctrlProps/ctrlProp9.xml'  => in_array($civilStatus, ['other/s','others'], true),
            'xl/ctrlProps/ctrlProp10.xml' => $civilStatus === 'separated',
            'xl/ctrlProps/ctrlProp2.xml'  => $citizenship === 'filipino',
            'xl/ctrlProps/ctrlProp3.xml'  => $citizenship === 'dual citizenship',
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
            'xl/ctrlProps/ctrlProp21.xml' => $q36  === 'yes',
            'xl/ctrlProps/ctrlProp22.xml' => $q36  === 'no',
            'xl/ctrlProps/ctrlProp23.xml' => $q37  === 'yes',
            'xl/ctrlProps/ctrlProp24.xml' => $q37  === 'no',
            'xl/ctrlProps/ctrlProp34.xml' => $q38a === 'yes',
            'xl/ctrlProps/ctrlProp35.xml' => $q38a === 'no',
            'xl/ctrlProps/ctrlProp36.xml' => $q38b === 'yes',
            'xl/ctrlProps/ctrlProp37.xml' => $q38b === 'no',
            'xl/ctrlProps/ctrlProp25.xml' => $q39  === 'yes',
            'xl/ctrlProps/ctrlProp26.xml' => $q39  === 'no',
            'xl/ctrlProps/ctrlProp27.xml' => $q40a === 'yes',
            'xl/ctrlProps/ctrlProp30.xml' => $q40a === 'no',
            'xl/ctrlProps/ctrlProp28.xml' => $q40b === 'yes',
            'xl/ctrlProps/ctrlProp31.xml' => $q40b === 'no',
            'xl/ctrlProps/ctrlProp29.xml' => $q40c === 'yes',
            'xl/ctrlProps/ctrlProp32.xml' => $q40c === 'no',
        ];

        $zip = new \ZipArchive();
        if ($zip->open($filePath, \ZipArchive::CREATE) !== true) {
            throw new Exception('Cannot open xlsx: ' . $filePath);
        }

        // Fix BUG 2: deduplicate duplicate VML relationships in C1 rels.
        $sheet1Rels = $zip->getFromName('xl/worksheets/_rels/sheet1.xml.rels');
        if ($sheet1Rels !== false) {
            $sheet1Rels = $this->deduplicateVmlRels($sheet1Rels);
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $sheet1Rels);
        }

        // Fix BUG 1: find actual post-save VML files by shape pattern.
        $c1VmlPath = $this->findVmlByShapePattern(
            $zip,
            'xl/worksheets/_rels/sheet1.xml.rels',
            '_x0000_s10'
        );
        $c4VmlPath = $this->findVmlByShapePattern(
            $zip,
            'xl/worksheets/_rels/sheet4.xml.rels',
            'Check_x0020_Box_x0020_'
        );

        Log::info('PDS checkbox VML paths', [
            'c1_vml' => $c1VmlPath,
            'c4_vml' => $c4VmlPath,
            'sex' => $sex,
            'civil' => $civilStatus,
            'citizenship' => $citizenship,
        ]);

        if ($c1VmlPath) {
            $vml = $zip->getFromName($c1VmlPath);
            if ($vml !== false) {
                $vml = $this->applyVmlChecks($vml, $c1States);
                $zip->addFromString($c1VmlPath, $vml);
                $this->applyWorksheetControlChecks(
                    $zip,
                    'xl/worksheets/sheet1.xml',
                    $vml,
                    $c1States
                );
            }
        }

        if ($c4VmlPath) {
            $vml = $zip->getFromName($c4VmlPath);
            if ($vml !== false) {
                $vml = $this->applyVmlChecks($vml, $c4States);
                $zip->addFromString($c4VmlPath, $vml);
                $this->applyWorksheetControlChecks(
                    $zip,
                    'xl/worksheets/sheet4.xml',
                    $vml,
                    $c4States
                );
            }
        }

        foreach ($ctrlPropMap as $file => $shouldCheck) {
            $content = $zip->getFromName($file);
            if ($content === false) {
                continue;
            }

            $content = str_replace(' checked="1"', '', $content);
            if ($shouldCheck) {
                $content = str_replace('/>', ' checked="1"/>', $content);
            }

            $zip->addFromString($file, $content);
        }

        $zip->close();
    }

    private function findVmlByShapePattern(
        \ZipArchive $zip,
        string $relsPath,
        string $shapePattern
    ): ?string
    {
        $relsContent = $zip->getFromName($relsPath);
        if ($relsContent === false) {
            return null;
        }

        preg_match_all('/Target="\.\.\/drawings\/(vmlDrawing\d+\.vml)"/i', $relsContent, $matches);

        if (empty($matches[1])) {
            return null;
        }

        $uniqueVmlFiles = array_unique($matches[1]);

        foreach ($uniqueVmlFiles as $vmlFilename) {
            $fullPath = 'xl/drawings/' . $vmlFilename;
            $content = $zip->getFromName($fullPath);
            if ($content !== false && str_contains($content, $shapePattern)) {
                return $fullPath;
            }
        }

        return null;
    }

    private function deduplicateVmlRels(string $relsContent): string
    {
        preg_match_all('/<Relationship[^>]+\/>/i', $relsContent, $matches);
        if (empty($matches[0])) {
            return $relsContent;
        }

        $seen = [];
        foreach ($matches[0] as $rel) {
            preg_match('/Target="([^"]+)"/i', $rel, $targetMatch);
            $target = $targetMatch[1] ?? $rel;

            if (in_array($target, $seen, true)) {
                $relsContent = str_replace($rel, '', $relsContent);
            } else {
                $seen[] = $target;
            }
        }

        return $relsContent;
    }

    private function applyVmlChecks(string $vml, array $shapeStates): string
    {
        foreach ($shapeStates as $shapeId => $shouldCheck) {
            $needle = 'id="' . $shapeId . '"';
            $shapePos = strpos($vml, $needle);
            if ($shapePos === false) {
                $needle = 'o:spid="' . $shapeId . '"';
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

            $clientStartTag = '<x:ClientData';
            $clientStartPos = strpos($shapeBlock, $clientStartTag);
            if ($clientStartPos === false) {
                continue;
            }

            $clientEndTag = '</x:ClientData>';
            $clientEndPos = strpos($shapeBlock, $clientEndTag, $clientStartPos);
            if ($clientEndPos === false) {
                continue;
            }

            $clientBlock = substr($shapeBlock, $clientStartPos, ($clientEndPos + strlen($clientEndTag)) - $clientStartPos);

            $clientBlock = str_replace('<x:Checked/>', '', $clientBlock);
            $clientBlock = str_replace('<x:Checked />', '', $clientBlock);
            $clientBlock = str_replace('<x:Checked>1</x:Checked>', '', $clientBlock);
            $clientBlock = str_replace('<x:Checked>0</x:Checked>', '', $clientBlock);
            $clientBlock = str_replace('<x:Checked>true</x:Checked>', '', $clientBlock);
            $clientBlock = str_replace('<x:Checked>Checked</x:Checked>', '', $clientBlock);

            if ($shouldCheck) {
                if (str_contains($clientBlock, '<x:NoThreeD/>')) {
                    $clientBlock = str_replace('<x:NoThreeD/>', "<x:Checked>1</x:Checked>\r\n   <x:NoThreeD/>", $clientBlock);
                } else {
                    $clientBlock = str_replace($clientEndTag, "<x:Checked>1</x:Checked>\r\n  $clientEndTag", $clientBlock);
                }
            }

            $updatedShapeBlock = substr($shapeBlock, 0, $clientStartPos)
                . $clientBlock
                . substr($shapeBlock, $clientEndPos + strlen($clientEndTag));

            $vml = substr($vml, 0, $shapeStart)
                . $updatedShapeBlock
                . substr($vml, $shapeEnd + strlen('</v:shape>'));
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
                    $attrs .= ' checked="Checked"';
                }

                return $matches[1] . $attrs . $matches[4];
            },
            $sheetXml
        ) ?? $sheetXml;

        $zip->addFromString($sheetXmlPath, $sheetXml);
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
