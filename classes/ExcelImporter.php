<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImporter
{
    private PDO $pdo;
    private CutoffRepository $repo;

    public function __construct(PDO $pdo, CutoffRepository $repo)
    {
        $this->pdo = $pdo;
        $this->repo = $repo;
    }

    public function import(string $filePath, int $year): array
    {
        if (!file_exists($filePath)) {
            throw new Exception('Excel file not found.');
        }

        $spreadsheet = IOFactory::load($filePath);

        $total = 0;
        $saved = 0;
        $skipped = 0;

        $this->pdo->beginTransaction();

        try {
            $yearId = $this->repo->getOrCreateYear($year);

            $this->importInstituteTypes($spreadsheet);

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {

                $sheetName = trim($sheet->getTitle());

                if (!preg_match('/^R(\d+)$/i', $sheetName, $matches)) {
                    continue;
                }

                $roundNo = (int)$matches[1];
                $roundName = strtoupper($sheetName);

                $roundId = $this->repo->getOrCreateRound($yearId, $roundNo, $roundName);

                $rows = $sheet->toArray();

                foreach ($rows as $index => $row) {

                    if ($index === 0) {
                        continue;
                    }

                    $total++;

                    $instituteName = cleanString($row[0] ?? '');
                    $programName   = cleanString($row[1] ?? '');
                    $quotaCode     = cleanString($row[2] ?? '');
                    $seatTypeCode  = cleanString($row[3] ?? '');
                    $genderName    = cleanString($row[4] ?? '');

                    $opening = RankParser::parse($row[5] ?? '');
                    $closing = RankParser::parse($row[6] ?? '');

                    if (
                        $instituteName === '' ||
                        $programName === '' ||
                        $quotaCode === '' ||
                        $seatTypeCode === '' ||
                        $genderName === '' ||
                        $opening['rank'] <= 0 ||
                        $closing['rank'] <= 0
                    ) {
                        $skipped++;
                        continue;
                    }

                    $isPreparatory = $opening['is_preparatory'] || $closing['is_preparatory'];

                    $instituteId = $this->repo->saveInstitute($instituteName);
                    $programId   = $this->repo->getOrCreateProgram($instituteId, $programName);
                    $quotaId     = $this->repo->getOrCreateQuota($quotaCode);
                    $seatTypeId  = $this->repo->getOrCreateSeatType($seatTypeCode);
                    $genderId    = $this->repo->getOrCreateGender($genderName);

                    $this->repo->saveCutoff([
                        'year_id' => $yearId,
                        'round_id' => $roundId,
                        'institute_id' => $instituteId,
                        'program_id' => $programId,
                        'quota_id' => $quotaId,
                        'seat_type_id' => $seatTypeId,
                        'gender_id' => $genderId,
                        'opening_rank' => $opening['rank'],
                        'closing_rank' => $closing['rank'],
                        'opening_rank_raw' => $opening['raw'],
                        'closing_rank_raw' => $closing['raw'],
                        'is_preparatory' => $isPreparatory ? 1 : 0
                    ]);

                    $saved++;
                }
            }

            $this->pdo->commit();

            return [
                'total' => $total,
                'saved' => $saved,
                'skipped' => $skipped
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function importInstituteTypes($spreadsheet): void
    {
        $typeSheets = ['IIT', 'NIT', 'IIIT', 'GFTI'];

        foreach ($typeSheets as $typeCode) {

            $sheet = $spreadsheet->getSheetByName($typeCode);

            if (!$sheet) {
                continue;
            }

            $typeId = $this->repo->getOrCreateInstituteType($typeCode);

            $rows = $sheet->toArray();

            foreach ($rows as $row) {
                $instituteName = cleanString($row[0] ?? '');

                if ($instituteName === '') {
                    continue;
                }

                $this->repo->saveInstitute($instituteName, $typeId);
            }
        }
    }
}
