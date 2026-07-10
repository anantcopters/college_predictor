<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

class NeetUploadService
{
    public function __construct(private PDO $pdo) {}

    public function import(string $filePath, int $year, int $roundNo, string $fileName): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $this->pdo->beginTransaction();

        try {
            $uploadId = $this->createUpload($year, $roundNo, $fileName);

            $this->pdo->prepare("DELETE FROM neet_allotments WHERE upload_id = ?")
                ->execute([$uploadId]);

            $headers = array_map('trim', $rows[1]);
            unset($rows[1]);

            $saved = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                $data = $this->mapRow($headers, $row);

                if (empty($data['Rank'])) {
                    $skipped++;
                    continue;
                }

                $parsed = $this->parseRow($data, $year, $roundNo);
                $this->saveRow($uploadId, $parsed, $data);
                $saved++;
            }

            $this->pdo->commit();

            return ['saved' => $saved, 'skipped' => $skipped];

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function createUpload(int $year, int $roundNo, string $fileName): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO neet_round_uploads(year, round_no, file_name)
            VALUES(:year, :round_no, :file_name)
            ON CONFLICT(year, round_no)
            DO UPDATE SET file_name = EXCLUDED.file_name, uploaded_at = NOW()
            RETURNING id
        ");

        $stmt->execute([
            'year' => $year,
            'round_no' => $roundNo,
            'file_name' => $fileName
        ]);

        return (int)$stmt->fetchColumn();
    }

    private function mapRow(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $col => $header) {
            if ($header !== '') {
                $data[$header] = trim((string)($row[$col] ?? ''));
            }
        }

        return $data;
    }

    private function get(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $v = trim((string)$data[$key]);
                if ($v !== '' && $v !== '-') {
                    return $v;
                }
            }
        }

        return null;
    }

    private function parseRow(array $data, int $year, int $roundNo): array
    {
        $rank = (int)$this->get($data, ['Rank']);
        $sno = (int)($this->get($data, ['SNo', 'S.No']) ?? 0);

        if ($roundNo === 1) {
            $quota = $this->get($data, ['Allotted Quota']);
            $inst = $this->get($data, ['Allotted Institute']);
            $course = $this->get($data, ['Course']);
            $cat = $this->get($data, ['Alloted Category', 'Allotted Category']);
            $candidateCat = $this->get($data, ['Candidate Category']);
            $remarks = $this->get($data, ['Remarks']) ?? 'Allotted';

            return compact('year', 'roundNo', 'sno', 'rank') + [
                'previous_quota' => null,
                'previous_institute' => null,
                'previous_course' => null,
                'previous_remarks' => null,
                'current_quota' => $quota,
                'current_institute' => $inst,
                'current_course' => $course,
                'allotted_category' => $cat,
                'candidate_category' => $candidateCat,
                'option_no' => null,
                'remarks' => $remarks,
                'final_quota' => $quota,
                'final_institute' => $inst,
                'final_course' => $course,
                'final_category' => $cat,
                'status' => 'FRESH_ALLOTTED_R1',
                'is_upgraded' => false,
                'is_fresh_allotted' => true,
                'is_active_seat' => true
            ];
        }

        $prev = 'R' . ($roundNo - 1);
        $curr = 'R' . $roundNo;

        $prevQuota = $this->get($data, [$prev . ' Allotted Quota', 'Round 1 Allotted Quota']);
        $prevInst = $this->get($data, [$prev . ' Allotted Institute', 'Round 1 Allotted Institute']);
        $prevCourse = $this->get($data, [$prev . ' Course', 'Round 1 Course']);
        $prevRemarks = $this->get($data, [$prev . ' Remarks', 'Round 1 Remarks']);

        $quota = $this->get($data, [$curr . ' Allotted Quota', 'Allotted Quota']);
        $inst = $this->get($data, [$curr . ' Allotted Institute', 'Allotted Institute']);
        $course = $this->get($data, [$curr . ' Course', 'Course']);
        $cat = $this->get($data, [$curr . ' Alloted Category', $curr . ' Allotted Category', 'Alloted Category']);
        $candidateCat = $this->get($data, [$curr . ' Candidate Category', $curr . ' candidate Category', 'Candidate Category']);
        $optionNo = $this->get($data, [$curr . ' Option No', $curr . ' option No', 'option No']);
        $remarks = $this->get($data, [$curr . ' Remarks', 'Remarks']) ?? '';

        $hasCurrent = $quota && $inst;
        $status = $this->detectStatus($remarks);
        $isUpgraded = stripos($remarks, 'upgraded') !== false;
        $isFresh = stripos($remarks, 'fresh allotted') !== false;

        return compact('year', 'roundNo', 'sno', 'rank') + [
            'previous_quota' => $prevQuota,
            'previous_institute' => $prevInst,
            'previous_course' => $prevCourse,
            'previous_remarks' => $prevRemarks,
            'current_quota' => $quota,
            'current_institute' => $inst,
            'current_course' => $course,
            'allotted_category' => $cat,
            'candidate_category' => $candidateCat,
            'option_no' => $optionNo ? (int)$optionNo : null,
            'remarks' => $remarks,
            'final_quota' => $hasCurrent ? $quota : $prevQuota,
            'final_institute' => $hasCurrent ? $inst : $prevInst,
            'final_course' => $hasCurrent ? $course : $prevCourse,
            'final_category' => $cat,
            'status' => $status,
            'is_upgraded' => $isUpgraded,
            'is_fresh_allotted' => $isFresh,
            'is_active_seat' => !empty($hasCurrent ? $inst : $prevInst)
        ];
    }

    private function detectStatus(string $remarks): string
    {
        $r = strtolower($remarks);

        if (str_contains($r, 'fresh allotted')) return 'FRESH_ALLOTTED';
        if (str_contains($r, 'upgraded')) return 'UPGRADED';
        if (str_contains($r, 'no upgradation')) return 'NO_UPGRADATION';
        if (str_contains($r, 'did not opt')) return 'DID_NOT_OPT_UPGRADATION';
        if (str_contains($r, 'did not fill')) return 'DID_NOT_FILL_CHOICES';
        if (str_contains($r, 'not allotted')) return 'NOT_ALLOTTED';
        if (str_contains($r, 'seat surrendered')) return 'SEAT_SURRENDERED';

        return 'RETAINED';
    }

    private function saveRow(int $uploadId, array $p, array $raw): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO neet_allotments (
                upload_id, year, round_no, sno, rank,
                previous_quota, previous_institute, previous_course, previous_remarks,
                current_quota, current_institute, current_course,
                allotted_category, candidate_category, option_no, remarks,
                final_quota, final_institute, final_course, final_category,
                status, is_upgraded, is_fresh_allotted, is_active_seat,
                raw_json, row_hash
            ) VALUES (
                :upload_id, :year, :round_no, :sno, :rank,
                :previous_quota, :previous_institute, :previous_course, :previous_remarks,
                :current_quota, :current_institute, :current_course,
                :allotted_category, :candidate_category, :option_no, :remarks,
                :final_quota, :final_institute, :final_course, :final_category,
                :status, :is_upgraded, :is_fresh_allotted, :is_active_seat,
                :raw_json, :row_hash
            )
        ");

        $stmt->execute([
            'upload_id' => $uploadId,
            'year' => $p['year'],
            'round_no' => $p['roundNo'],
            'sno' => $p['sno'],
            'rank' => $p['rank'],
            'previous_quota' => $p['previous_quota'],
            'previous_institute' => $p['previous_institute'],
            'previous_course' => $p['previous_course'],
            'previous_remarks' => $p['previous_remarks'],
            'current_quota' => $p['current_quota'],
            'current_institute' => $p['current_institute'],
            'current_course' => $p['current_course'],
            'allotted_category' => $p['allotted_category'],
            'candidate_category' => $p['candidate_category'],
            'option_no' => $p['option_no'],
            'remarks' => $p['remarks'],
            'final_quota' => $p['final_quota'],
            'final_institute' => $p['final_institute'],
            'final_course' => $p['final_course'],
            'final_category' => $p['final_category'],
            'status' => $p['status'],
            'is_upgraded' => $p['is_upgraded'] ? 1 : 0,
            'is_fresh_allotted' => $p['is_fresh_allotted'] ? 1 : 0,
            'is_active_seat' => $p['is_active_seat'] ? 1 : 0,
            'raw_json' => json_encode($raw),
            'row_hash' => sha1(json_encode($raw))
        ]);
    }
}