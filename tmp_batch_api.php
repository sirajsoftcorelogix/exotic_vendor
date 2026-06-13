<?php
foreach (['DDN329', 'HBG309,DDN329', 'DDN329,HBG309'] as $codes) {
    echo "=== fetch: $codes ===\n";
    $url = 'https://www.exoticindia.com/vendor-api/product/fetch?itemcodes=' . urlencode($codes);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
        'x-adminapitest: 1',
    ]);
    $d = json_decode(curl_exec($ch), true);
    curl_close($ch);
    foreach (explode(',', $codes) as $code) {
        $code = trim($code);
        $ls = $d[$code]['local_stock'] ?? 'KEY_MISSING';
        echo "  $code local_stock=" . json_encode($ls) . "\n";
    }
    echo "\n";
}
