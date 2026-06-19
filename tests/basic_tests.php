<?php
function normalize_phone($phone) {
    $phone = preg_replace('/\D+/', '', $phone);
    if (strpos($phone, '0') === 0) {
        return '254' . substr($phone, 1);
    } elseif (strpos($phone, '7') === 0) {
        return '254' . $phone;
    }
    return $phone;
}

$cases = [
    ['in' => '0712345678', 'out' => '254712345678'],
    ['in' => '+254712345678', 'out' => '254712345678'],
    ['in' => '712345678', 'out' => '254712345678'],
    ['in' => '254712345678', 'out' => '254712345678'],
    ['in' => '(071) 234-5678', 'out' => '254712345678'],
];

$failures = [];
foreach ($cases as $c) {
    $got = normalize_phone($c['in']);
    if ($got !== $c['out']) {
        $failures[] = [ 'in' => $c['in'], 'expected' => $c['out'], 'got' => $got ];
    }
}

if (empty($failures)) {
    echo "All tests passed.\n";
    exit(0);
}

echo count($failures) . " tests failed:\n";
foreach ($failures as $f) {
    echo "Input: {$f['in']} Expected: {$f['expected']} Got: {$f['got']}\n";
}
exit(2);
