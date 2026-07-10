<?php

class NeetSearchService
{
    public function __construct(private PDO $pdo) {}

    public function getYears(): array
    {
        return $this->pdo->query("
            SELECT DISTINCT year
            FROM neet_round_uploads
            ORDER BY year DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQuotas(int $year): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT final_quota
            FROM neet_allotments
            WHERE year = :year
              AND final_quota IS NOT NULL
              AND final_quota <> ''
            ORDER BY final_quota
        ");
        $stmt->execute(['year' => $year]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function search(int $year, int $rank, int $threshold, string $quota): array
    {
        $minRank = max(1, $rank - $threshold);
        $maxRank = $rank + $threshold;

        $stmt = $this->pdo->prepare("
            WITH latest AS (
                SELECT *,
                       ROW_NUMBER() OVER (
                           PARTITION BY rank
                           ORDER BY round_no DESC
                       ) AS rn
                FROM neet_allotments
                WHERE year = :year
                  AND rank BETWEEN :min_rank AND :max_rank
                  AND final_quota = :quota
                  AND is_active_seat = TRUE
            )
            SELECT *
            FROM latest
            WHERE rn = 1
            ORDER BY rank ASC
        ");

        $stmt->execute([
            'year' => $year,
            'min_rank' => $minRank,
            'max_rank' => $maxRank,
            'quota' => $quota
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}