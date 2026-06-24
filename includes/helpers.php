<?php

function cleanString(?string $value): string
{
    if ($value === null) {
        return '';
    }

    // Convert to string
    $value = (string)$value;

    // Replace non-breaking spaces with normal spaces
    $value = str_replace("\xC2\xA0", ' ', $value);

    // Replace tabs/newlines with spaces
    $value = preg_replace('/[\r\n\t]+/', ' ', $value);

    // Collapse multiple spaces into one
    $value = preg_replace('/\s+/u', ' ', $value);

    // Trim
    return trim($value);
}

function normalizeKey(string $value): string
{
    $value = cleanString($value);

    return strtoupper($value);
}
