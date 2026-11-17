<?php
function parse_date($value) {
    if ($value === null || $value === '') {
        return null;
    }

    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
        return null;
    }

    return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]) ? $value : null;
}

function parse_time($value) {
    if ($value === null || $value === '') {
        return null;
    }

    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value)) {
        return null;
    }

    return strlen($value) === 5 ? $value . ':00' : $value;
}

function parse_measured_at($value, $fallback) {
    if ($value === '') {
        return $fallback;
    }

    $normalized = str_replace('T', ' ', $value);
    if (strlen($normalized) === 16) {
        $normalized .= ':00';
    }

    $date = substr($normalized, 0, 10);
    $time = substr($normalized, 11);

    if (parse_date($date) === null || parse_time($time) === null) {
        return false;
    }

    return $date . ' ' . parse_time($time);
}
