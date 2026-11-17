<?php
declare(strict_types=1);

// Strict Y-m-d parser shared by the food, medication and report endpoints.
function parse_date(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

// Strict H:i (or H:i:s) parser, normalised to H:i:s for a TIME column.
function parse_time(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    foreach (['!H:i', '!H:i:s'] as $format) {
        $time = DateTimeImmutable::createFromFormat($format, $value);
        if ($time && $time->format($format === '!H:i' ? 'H:i' : 'H:i:s') === $value) {
            return $time->format('H:i:s');
        }
    }

    return null;
}
