<?php
const GLUCOSE_TIMINGS = ['fasting', 'random', 'post_meal'];

function classifyGlucose($mgdl, $timing) {
    $bands = [
        'fasting' => ['normal' => 130, 'elevated' => 179],
        'random' => ['normal' => 179, 'elevated' => 199],
        'post_meal' => ['normal' => 179, 'elevated' => 249],
    ];

    if (!isset($bands[$timing])) {
        throw new Exception('Unknown measurement timing: ' . $timing);
    }

    if ($mgdl < 70) {
        return 'hypoglycemia';
    }

    if ($mgdl <= $bands[$timing]['normal']) {
        return 'normal';
    }

    if ($mgdl <= $bands[$timing]['elevated']) {
        return 'elevated';
    }

    return 'high';
}
