<?php

const COURIER_TOP_LIMIT = 10;

/** Economy ranking: blend performance (higher), freight (lower), ETD days (lower). Min–max per response. Tune weights to taste (must sum to 1). */
const ECONOMY_WEIGHT_PERFORMANCE = 0.28;
const ECONOMY_WEIGHT_FREIGHT = 0.55;
const ECONOMY_WEIGHT_ETD = 0.17;

/*
|--------------------------------------------------------------------------
| EXPRESS SHIPPING RANKING
| Priority:
| Reliable → Best → Fastest
|--------------------------------------------------------------------------
*/
/** Non-stressed couriers sort before stressed (stressed pushed to end of lists). */
function compareStressLast($a, $b)
{
    $sa = !empty($a['stress_flag']) ? 1 : 0;
    $sb = !empty($b['stress_flag']) ? 1 : 0;

    return $sa <=> $sb;
}

function compareExpressCouriers($a, $b)
{
    // Stress flag sorting is temporarily disabled.
    // $stressCmp = compareStressLast($a, $b);
    // if ($stressCmp !== 0) {
    //     return $stressCmp;
    // }

    $perfA = performanceScore($a);
    $perfB = performanceScore($b);

    if ($perfA !== $perfB) {
        return $perfB <=> $perfA;
    }
    if ($a['etd'] !== $b['etd']) {
        return $a['etd'] <=> $b['etd'];
    }

    return $b['rating'] <=> $a['rating'];
}

function rankExpress($couriers)
{
    usort($couriers, 'compareExpressCouriers');

    return array_slice($couriers, 0, COURIER_TOP_LIMIT);
}

/*
|--------------------------------------------------------------------------
| ECONOMY SHIPPING RANKING
| Priority:
| Stress last → composite (cheap + good performance + low ETD) → tie-breakers
|--------------------------------------------------------------------------
*/
function normInRange($value, $min, $max)
{
    if ($max <= $min) {
        return 0.5;
    }

    return ($value - $min) / ($max - $min);
}

/** Sets economy_composite on each row (higher = better for economy). Call before compareEconomyCouriers / usort. */
function assignEconomyCompositeScores(array &$couriers)
{
    $n = count($couriers);
    if ($n === 0) {
        return;
    }

    $perfs = [];
    foreach ($couriers as $c) {
        $perfs[] = performanceScore($c);
    }
    $freights = array_column($couriers, 'freight');
    $etds = array_column($couriers, 'etd');

    $minP = min($perfs);
    $maxP = max($perfs);
    $minF = min($freights);
    $maxF = max($freights);
    $minE = min($etds);
    $maxE = max($etds);

    foreach ($couriers as $i => &$c) {
        $pN = normInRange($perfs[$i], $minP, $maxP);
        $fN = normInRange($freights[$i], $minF, $maxF);
        $eN = normInRange($etds[$i], $minE, $maxE);

        $c['economy_composite'] =
            ECONOMY_WEIGHT_PERFORMANCE * $pN
            + ECONOMY_WEIGHT_FREIGHT * (1 - $fN)
            + ECONOMY_WEIGHT_ETD * (1 - $eN);
    }
    unset($c);
}

function compareEconomyCouriers($a, $b)
{
    // Stress flag sorting is temporarily disabled.
    // $stressCmp = compareStressLast($a, $b);
    // if ($stressCmp !== 0) {
    //     return $stressCmp;
    // }

    if (isset($a['economy_composite'], $b['economy_composite'])) {
        $cmp = $b['economy_composite'] <=> $a['economy_composite'];
        if ($cmp !== 0) {
            return $cmp;
        }
    }

    if ($a['freight'] !== $b['freight']) {
        return $a['freight'] <=> $b['freight'];
    }
    if ($a['etd'] !== $b['etd']) {
        return $a['etd'] <=> $b['etd'];
    }

    $perfA = performanceScore($a);
    $perfB = performanceScore($b);
    if ($perfA !== $perfB) {
        return $perfB <=> $perfA;
    }

    return $b['rating'] <=> $a['rating'];
}

function rankEconomy($couriers)
{
    assignEconomyCompositeScores($couriers);
    usort($couriers, 'compareEconomyCouriers');

    return array_slice($couriers, 0, COURIER_TOP_LIMIT);
}

/*
|--------------------------------------------------------------------------
| PERFORMANCE SCORE
|--------------------------------------------------------------------------
*/
function performanceScore($c)
{
    return
        ($c['delivery_performance'] ?? 0) +
        ($c['pickup_performance'] ?? 0) +
        ($c['rto_performance'] ?? 0) +
        ($c['tracking_performance'] ?? 0) +
        ($c['sla_adherence'] ?? 0) -
        ($c['sla_breach'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| ETD NORMALIZER
| Examples:
| 2-3 days → 3
| 3 days   → 3
|--------------------------------------------------------------------------
*/
function parseETD($etd)
{
    preg_match_all('/\d+/', $etd, $matches);

    if (empty($matches[0]))
        return 999;

    $nums = array_map('intval', $matches[0]);

    return ceil(array_sum($nums) / count($nums));
}

/** Numeric sort key for delivery time (Shiprocket: days / hours / date text). */
function courierEtdDays(array $c)
{
    if (isset($c['estimated_delivery_days']) && $c['estimated_delivery_days'] !== '') {
        $d = (int)$c['estimated_delivery_days'];
        if ($d > 0) {
            return $d;
        }
    }
    if (!empty($c['etd_hours'])) {
        return max(1, (int)ceil((float)$c['etd_hours'] / 24));
    }

    return parseETD((string)($c['etd'] ?? ''));
}

function calculateConfidence($c)
{
    $delivery  = normalize($c['delivery_performance']);
    $rating    = normalize($c['rating']);
    $rto       = normalize($c['rto_performance']);
    $pickup    = normalize($c['pickup_performance']);
    $tracking  = normalize($c['tracking_performance']);

    // Faster delivery = higher score
    $speed = 1 / max($c['etd_days'], 1);

    // Reliability penalty
    $reliability = 1;
    if ($c['stress_flag']) $reliability -= 0.5;
    if ($c['SLA_Breach'] > 0) $reliability -= 0.3;
    if ($c['pickup_availability'] != 1) $reliability -= 0.5;

    $score =
        ($delivery * 25) +
        ($rating * 20) +
        ($rto * 20) +
        ($pickup * 10) +
        ($tracking * 10) +
        ($speed * 10) +
        ($reliability * 5);

    return round(max(min($score,100),0));
}

/*
|--------------------------------------------------------------------------
| Courier Selection Engine (Shiprocket)
|--------------------------------------------------------------------------
| Rules:
| - COD / Non-COD supported
| - Express / Non Express supported
| - Reliable first
| - Returns Top 5
| - Auto select Top 1
*/

/** Human-readable reasons a courier fails eligibility for prepareCouriers (all that apply). */
function courierExclusionReasons(array $c, $isCOD)
{
    $reasons = [];

    if ($isCOD && empty($c['cod'])) {
        $reasons[] = 'COD is required for this order but the courier does not support COD.';
    }

    if ((int)($c['pickup_availability'] ?? 0) !== 1) {
        $pa = $c['pickup_availability'] ?? null;
        $reasons[] = 'Reliability filter: pickup not available at pickup location (pickup_availability must be 1; got '
            . json_encode($pa) . ').';
    }

    return $reasons;
}

function prepareCouriers($shiprocketResponse, $isCOD = false, $isExpress = false)
{
    $empty = [
        'topCourier' => [],
        'excludedFromFilters' => [],
        'eligibleNotInTop' => [],
    ];

    if (!isset($shiprocketResponse['data']['available_courier_companies'])) {
        return $empty;
    }

    $normalized = [];
    $excludedFromFilters = [];

    foreach ($shiprocketResponse['data']['available_courier_companies'] as $c) {
        $reasons = courierExclusionReasons($c, $isCOD);
        if (!empty($reasons)) {
            $excludedFromFilters[] = [
                'id' => $c['courier_company_id'] ?? null,
                'name' => $c['courier_name'] ?? '',
                'reasons' => $reasons,
            ];
            continue;
        }

        $normalized[] = [
            'id'        => $c['courier_company_id'],
            'name'      => $c['courier_name'],
            'rating'    => $c['rating'] ?? 0,
            'etd'       => courierEtdDays($c),
            'freight'   => $c['freight_charge'] ?? 999999,

            'delivery_performance' => $c['delivery_performance'] ?? 0,
            'pickup_performance'   => $c['pickup_performance'] ?? 0,
            'rto_performance'      => $c['rto_performance'] ?? 0,
            'tracking_performance' => $c['tracking_performance'] ?? 0,

            'sla_breach'    => $c['SLA_Breach'] ?? 1,
            'sla_adherence' => $c['SLA_Adherence'] ?? 0,

            'stress_flag' => !empty($c['stress_flag']),
        ];
    }

    $sorted = $normalized;
    if ($isExpress) {
        usort($sorted, 'compareExpressCouriers');
    } else {
        assignEconomyCompositeScores($sorted);
        usort($sorted, 'compareEconomyCouriers');
    }

    $topCourier = array_slice($sorted, 0, COURIER_TOP_LIMIT);
    foreach ($topCourier as $i => &$tcRow) {
        $tcRow['selected'] = $i === 0;
    }
    unset($tcRow);

    $eligibleNotInTop = [];

    foreach (array_slice($sorted, COURIER_TOP_LIMIT) as $row) {
        $eligibleNotInTop[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'reasons' => [
                'Passed all filters but ranked outside the top ' . COURIER_TOP_LIMIT . ' for '
                . ($isExpress ? 'express' : 'economy') . ' rules (performance, '
                . ($isExpress ? 'speed, rating' : 'price, speed, rating') . ').',
            ],
            'freight' => $row['freight'],
            'etd_days' => $row['etd'],
            'performanceScore' => performanceScore($row),
        ];
    }

    return [
        'topCourier' => $topCourier,
        'excludedFromFilters' => $excludedFromFilters,
        'eligibleNotInTop' => $eligibleNotInTop,
    ];
}

/*
|--------------------------------------------------------------------------
| Demo: load local JSON, call prepareCouriers, print (browser or CLI)
|--------------------------------------------------------------------------
*/
// $fixture = __DIR__ . DIRECTORY_SEPARATOR . 'shipRocketServicibility.txt';

// if (PHP_SAPI !== 'cli') {
//     header('Content-Type: text/plain; charset=utf-8');
// }

// if (!is_readable($fixture)) {
//     $msg = "Fixture not readable: {$fixture}\n";
//     echo $msg;
//     if (PHP_SAPI === 'cli') {
//         fwrite(STDERR, $msg);
//         exit(1);
//     }
//     return;
// }

// $payload = json_decode(file_get_contents($fixture), true);
// if (!is_array($payload)) {
//     $msg = "Invalid JSON in fixture.\n";
//     echo $msg;
//     if (PHP_SAPI === 'cli') {
//         fwrite(STDERR, $msg);
//         exit(1);
//     }
//     return;
// }

// $cases = [
//    // 'economy, non-COD' => [false, false],
//   //  'economy, COD' => [true, false],
//    // 'express, non-COD' => [false, true],
//     'express, COD' => [true, true],
// ];

// foreach ($cases as $label => [$cod, $express]) {
//     $out = prepareCouriers($payload, $cod, $express);
//     echo "=== {$label} ===\n";
//     echo json_encode(
//         [
//             'topCourier' => array_map(function ($row) {
//                 $summary = [
//                     'id' => $row['id'],
//                     'name' => $row['name'],
//                     'selected' => !empty($row['selected']),
//                     'stress_flag' => !empty($row['stress_flag']),
//                     'freight' => $row['freight'],
//                     'etd_days' => $row['etd'],
//                     'performanceScore' => performanceScore($row),
//                 ];
//                 if (isset($row['economy_composite'])) {
//                     $summary['economy_composite'] = round($row['economy_composite'], 4);
//                 }

//                 return $summary;
//             }, $out['topCourier']),
//             'excludedFromFilters' => $out['excludedFromFilters'],
//             'eligibleNotInTop' => $out['eligibleNotInTop'],
//         ],
//         JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
//     ) . "\n\n";
// }

// if (PHP_SAPI === 'cli') {
//     exit(0);
// }