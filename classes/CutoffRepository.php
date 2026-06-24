<?php

class CutoffRepository
{
    private PDO $pdo;

    private array $cache = [
        'years' => [],
        'rounds' => [],
        'types' => [],
        'institutes' => [],
        'programs' => [],
        'quotas' => [],
        'seat_types' => [],
        'genders' => [],
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getOrCreateYear(int $year): int
    {
        if (isset($this->cache['years'][$year])) {
            return $this->cache['years'][$year];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO counselling_years (year)
            VALUES (:year)
            ON CONFLICT (year)
            DO UPDATE SET year = EXCLUDED.year
            RETURNING id
        ");
        $stmt->execute(['year' => $year]);

        return $this->cache['years'][$year] = (int)$stmt->fetchColumn();
    }

    public function getOrCreateRound(int $yearId, int $roundNo, string $roundName): int
    {
        $key = $yearId . '_' . $roundNo;

        if (isset($this->cache['rounds'][$key])) {
            return $this->cache['rounds'][$key];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO rounds (year_id, round_no, round_name)
            VALUES (:year_id, :round_no, :round_name)
            ON CONFLICT (year_id, round_no)
            DO UPDATE SET round_name = EXCLUDED.round_name
            RETURNING id
        ");

        $stmt->execute([
            'year_id' => $yearId,
            'round_no' => $roundNo,
            'round_name' => $roundName
        ]);

        return $this->cache['rounds'][$key] = (int)$stmt->fetchColumn();
    }

    public function getOrCreateInstituteType(string $code): int
    {
        $code = cleanString($code);

        if (isset($this->cache['types'][$code])) {
            return $this->cache['types'][$code];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO institute_types (code)
            VALUES (:code)
            ON CONFLICT (code)
            DO UPDATE SET code = EXCLUDED.code
            RETURNING id
        ");
        $stmt->execute(['code' => $code]);

        return $this->cache['types'][$code] = (int)$stmt->fetchColumn();
    }

    public function saveInstitute(string $name, ?int $typeId = null): int
    {
        $name = normalizeKey($name);

        if (isset($this->cache['institutes'][$name])) {
            return $this->cache['institutes'][$name];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO institutes (name, institute_type_id)
            VALUES (:name, :type_id)
            ON CONFLICT (name)
            DO UPDATE SET institute_type_id = COALESCE(EXCLUDED.institute_type_id, institutes.institute_type_id)
            RETURNING id
        ");

        $stmt->execute([
            'name' => $name,
            'type_id' => $typeId
        ]);

        return $this->cache['institutes'][$name] = (int)$stmt->fetchColumn();
    }

    public function getOrCreateProgram(int $instituteId, string $programName): int
    {
        $programName = cleanString($programName);
        $key = $instituteId . '_' . md5($programName);

        if (isset($this->cache['programs'][$key])) {
            return $this->cache['programs'][$key];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO programs (institute_id, program_name)
            VALUES (:institute_id, :program_name)
            ON CONFLICT (institute_id, program_name)
            DO UPDATE SET program_name = EXCLUDED.program_name
            RETURNING id
        ");

        $stmt->execute([
            'institute_id' => $instituteId,
            'program_name' => $programName
        ]);

        return $this->cache['programs'][$key] = (int)$stmt->fetchColumn();
    }

    public function getOrCreateQuota(string $code): int
    {
        return $this->getOrCreateSimple('quotas', 'code', $code, 'quotas');
    }

    public function getOrCreateSeatType(string $code): int
    {
        return $this->getOrCreateSimple('seat_types', 'code', $code, 'seat_types');
    }

    public function getOrCreateGender(string $name): int
    {
        return $this->getOrCreateSimple('genders', 'name', $name, 'genders');
    }

    private function getOrCreateSimple(string $table, string $column, string $value, string $cacheKey): int
    {
        $value = cleanString($value);

        if (isset($this->cache[$cacheKey][$value])) {
            return $this->cache[$cacheKey][$value];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO {$table} ({$column})
            VALUES (:value)
            ON CONFLICT ({$column})
            DO UPDATE SET {$column} = EXCLUDED.{$column}
            RETURNING id
        ");

        $stmt->execute(['value' => $value]);

        return $this->cache[$cacheKey][$value] = (int)$stmt->fetchColumn();
    }

    public function saveCutoff(array $data): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO cutoffs (
                year_id,
                round_id,
                institute_id,
                program_id,
                quota_id,
                seat_type_id,
                gender_id,
                opening_rank,
                closing_rank,
                opening_rank_raw,
                closing_rank_raw,
                is_preparatory
            )
            VALUES (
                :year_id,
                :round_id,
                :institute_id,
                :program_id,
                :quota_id,
                :seat_type_id,
                :gender_id,
                :opening_rank,
                :closing_rank,
                :opening_rank_raw,
                :closing_rank_raw,
                :is_preparatory
            )
            ON CONFLICT (
                year_id,
                round_id,
                institute_id,
                program_id,
                quota_id,
                seat_type_id,
                gender_id,
                is_preparatory
            )
            DO UPDATE SET
                opening_rank = EXCLUDED.opening_rank,
                closing_rank = EXCLUDED.closing_rank,
                opening_rank_raw = EXCLUDED.opening_rank_raw,
                closing_rank_raw = EXCLUDED.closing_rank_raw
        ");

        $stmt->execute($data);
    }
}
