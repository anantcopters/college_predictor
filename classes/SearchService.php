<?php

class SearchService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getYears(): array
    {
        $stmt = $this->pdo->query("
            SELECT year
            FROM counselling_years
            ORDER BY year DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategories(): array
    {
        $stmt = $this->pdo->query("
            SELECT code
            FROM seat_types
            ORDER BY code
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search(array $filters): array
    {
        $year = (int)$filters['year'];
        $rank = (int)$filters['rank'];
        $category = cleanString($filters['category']);
        $threshold = (int)$filters['threshold'];
        $isJeeMain = (int)($filters['is_jee_main'] ?? 1);

        $minRank = max(1, $rank - $threshold);
        $maxRank = $rank + $threshold;

        $stmt = $this->pdo->prepare("
            WITH filtered AS (
            SELECT
                it.code AS institute_type,
                i.name AS institute,
                p.program_name,
                q.code AS quota,
                st.code AS category,
                g.name AS gender,
                r.round_name,
                r.round_no,
                c.opening_rank,
                c.closing_rank,
                c.opening_rank_raw,
                c.closing_rank_raw,
                c.is_preparatory,
                ROW_NUMBER() OVER (
                    PARTITION BY
                        i.id,
                        p.id,
                        q.id,
                        st.id,
                        g.id,
                        c.is_preparatory
                    ORDER BY r.round_no DESC
                ) AS rn
            FROM cutoffs c
            JOIN counselling_years y ON y.id = c.year_id
            JOIN rounds r ON r.id = c.round_id
            JOIN institutes i ON i.id = c.institute_id
            LEFT JOIN institute_types it ON it.id = i.institute_type_id
            JOIN programs p ON p.id = c.program_id
            JOIN quotas q ON q.id = c.quota_id
            JOIN seat_types st ON st.id = c.seat_type_id
            JOIN genders g ON g.id = c.gender_id
            WHERE y.year = :year
            AND st.code = :category
            AND c.opening_rank <= :max_rank
            AND c.closing_rank >= :min_rank
            AND (
                    (:is_jee_main = 1 AND COALESCE(it.code, '') <> 'IIT')
                    OR
                    (:is_jee_main = 0 AND it.code = 'IIT')
                )
        )
        SELECT *
        FROM filtered
        WHERE rn = 1
        ORDER BY closing_rank ASC, institute ASC, program_name ASC
        ");

        $stmt->execute([
            'year' => $year,
            'category' => $category,
            'min_rank' => $minRank,
            'max_rank' => $maxRank,
            'is_jee_main' => $isJeeMain
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
