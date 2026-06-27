<?php

class IatRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getYears(): array
    {
        return $this->pdo
            ->query("SELECT year FROM iat_years ORDER BY year DESC")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRoundsByYear(int $year): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.round_no, r.round_name
            FROM iat_rounds r
            JOIN iat_years y ON y.id = r.year_id
            WHERE y.year = :year
            ORDER BY r.round_no DESC
        ");
        $stmt->execute(['year' => $year]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInstitutes(): array
    {
        return $this->pdo
            ->query("SELECT id, name FROM iat_institutes ORDER BY display_order, name")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategories(): array
    {
        return $this->pdo
            ->query("SELECT id, code, display_name FROM iat_categories ORDER BY display_order")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrCreateYear(int $year): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO iat_years (year)
            VALUES (:year)
            ON CONFLICT (year)
            DO UPDATE SET year = EXCLUDED.year
            RETURNING id
        ");
        $stmt->execute(['year' => $year]);

        return (int)$stmt->fetchColumn();
    }

    public function getOrCreateRound(int $yearId, int $roundNo): int
    {
        $roundName = 'R' . $roundNo;

        $stmt = $this->pdo->prepare("
            INSERT INTO iat_rounds (year_id, round_no, round_name)
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

        return (int)$stmt->fetchColumn();
    }

    public function saveCutoff(
        int $year,
        int $roundNo,
        int $instituteId,
        int $categoryId,
        ?int $closingRank
    ): void {
        $yearId = $this->getOrCreateYear($year);
        $roundId = $this->getOrCreateRound($yearId, $roundNo);

        $stmt = $this->pdo->prepare("
            INSERT INTO iat_cutoffs (
                year_id,
                round_id,
                institute_id,
                category_id,
                closing_rank,
                updated_at
            )
            VALUES (
                :year_id,
                :round_id,
                :institute_id,
                :category_id,
                :closing_rank,
                NOW()
            )
            ON CONFLICT (year_id, round_id, institute_id, category_id)
            DO UPDATE SET
                closing_rank = EXCLUDED.closing_rank,
                updated_at = NOW()
        ");

        $stmt->execute([
            'year_id' => $yearId,
            'round_id' => $roundId,
            'institute_id' => $instituteId,
            'category_id' => $categoryId,
            'closing_rank' => $closingRank
        ]);
    }

    public function getMatrix(int $year, int $roundNo): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                ii.id AS institute_id,
                ii.name AS institute_name,
                ic.id AS category_id,
                ic.code AS category_code,
                ic.display_name AS category_name,
                c.closing_rank
            FROM iat_institutes ii
            CROSS JOIN iat_categories ic
            LEFT JOIN iat_years y ON y.year = :year
            LEFT JOIN iat_rounds r ON r.year_id = y.id AND r.round_no = :round_no
            LEFT JOIN iat_cutoffs c
                ON c.institute_id = ii.id
               AND c.category_id = ic.id
               AND c.year_id = y.id
               AND c.round_id = r.id
            ORDER BY ic.display_order, ii.display_order
        ");
        $stmt->execute([
            'year' => $year,
            'round_no' => $roundNo
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search(int $year, int $roundNo, int $rank, string $category): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                ii.name AS institute_name,
                ic.code AS category_code,
                ic.display_name AS category_name,
                r.round_name,
                c.closing_rank,
                CASE
                    WHEN c.closing_rank IS NULL THEN 'No Data'
                    WHEN :rank <= c.closing_rank THEN 'Possible'
                    ELSE 'Not Eligible'
                END AS status
            FROM iat_cutoffs c
            JOIN iat_years y ON y.id = c.year_id
            JOIN iat_rounds r ON r.id = c.round_id
            JOIN iat_institutes ii ON ii.id = c.institute_id
            JOIN iat_categories ic ON ic.id = c.category_id
            WHERE y.year = :year
              AND r.round_no = :round_no
              AND ic.code = :category
            ORDER BY
                CASE WHEN c.closing_rank IS NULL THEN 1 ELSE 0 END,
                c.closing_rank ASC,
                ii.display_order ASC
        ");

        $stmt->execute([
            'year' => $year,
            'round_no' => $roundNo,
            'rank' => $rank,
            'category' => $category
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchLatestRound(int $year, int $rank, string $category): array
    {
        $stmt = $this->pdo->prepare("
        WITH all_cutoffs AS (
            SELECT
                ii.id AS institute_id,
                ii.name AS institute_name,
                ii.display_order,
                ic.code AS category_code,
                ic.display_name AS category_name,
                r.round_name,
                r.round_no,
                c.closing_rank,
                CASE
                    WHEN :rank <= c.closing_rank THEN 1
                    ELSE 0
                END AS is_possible
            FROM iat_cutoffs c
            JOIN iat_years y ON y.id = c.year_id
            JOIN iat_rounds r ON r.id = c.round_id
            JOIN iat_institutes ii ON ii.id = c.institute_id
            JOIN iat_categories ic ON ic.id = c.category_id
            WHERE y.year = :year
              AND ic.code = :category
              AND c.closing_rank IS NOT NULL
        ),
        ranked AS (
            SELECT *,
                ROW_NUMBER() OVER (
                    PARTITION BY institute_id
                    ORDER BY
                        is_possible DESC,
                        CASE WHEN is_possible = 1 THEN round_no END ASC,
                        CASE WHEN is_possible = 0 THEN round_no END DESC
                ) AS rn
            FROM all_cutoffs
        )
        SELECT
            institute_name,
            category_code,
            category_name,
            round_name,
            round_no,
            closing_rank,
            CASE
                WHEN is_possible = 1 THEN 'Possible'
                ELSE 'Not Eligible'
            END AS status
        FROM ranked
        WHERE rn = 1
        ORDER BY
            is_possible DESC,
            closing_rank ASC,
            display_order ASC
    ");

        $stmt->execute([
            'year' => $year,
            'rank' => $rank,
            'category' => $category
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
