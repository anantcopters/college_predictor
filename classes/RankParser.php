<?php

class RankParser
{
    public static function parse($value): array
    {
        $raw = trim((string)$value);

        $isPreparatory = false;

        if ($raw !== '' && strtoupper(substr($raw, -1)) === 'P') {
            $isPreparatory = true;
        }

        $rank = (int)preg_replace('/[^0-9]/', '', $raw);

        return [
            'raw' => $raw,
            'rank' => $rank,
            'is_preparatory' => $isPreparatory
        ];
    }
}