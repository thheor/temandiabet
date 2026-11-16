<?php
declare(strict_types=1);

// Classification follows PERKENI — Pedoman Pengelolaan dan Pencegahan Diabetes
// Melitus Tipe 2 di Indonesia and Pedoman Pemantauan Glukosa Darah Mandiri
// (PGDM) — and ADA Standards of Care in Diabetes—2026, Section 6: Glycemic
// Goals and Hypoglycemia. Scope: non-pregnant adults with diabetes.
//
// | timing    | normal | elevated  | high   |
// | fasting   | 70–130 | 131–179   | >= 180 |
// | post_meal | 70–179 | 180–249   | >= 250 |
// | random    | 70–179 | 180–199   | >= 200 |
//
// < 70 mg/dL is hypoglycemia for every timing. The 54 and 250 refinements are
// display-only and live in the frontend; the stored enum stays four values.

const GLUCOSE_TIMINGS = ['fasting', 'random', 'post_meal'];

function classifyGlucose(int $mgdl, string $timing): string
{
    $bands = [
        'fasting' => ['normal' => 130, 'elevated' => 179],
        'random' => ['normal' => 179, 'elevated' => 199],
        'post_meal' => ['normal' => 179, 'elevated' => 249],
    ];

    if (!isset($bands[$timing])) {
        throw new InvalidArgumentException('Unknown measurement timing: ' . $timing);
    }

    if ($mgdl < 70) {
        return 'hypoglycemia';
    }

    return match (true) {
        $mgdl <= $bands[$timing]['normal'] => 'normal',
        $mgdl <= $bands[$timing]['elevated'] => 'elevated',
        default => 'high',
    };
}
