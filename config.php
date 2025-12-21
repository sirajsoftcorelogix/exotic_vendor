<?php
return [
  'db' => [
    'host' => 'localhost',
    'name' => 'vendor_portal_test',
    'user' => 'root',
    'pass' => 'SwAn@007',
    'charset' => 'utf8mb4'
  ],
  'token_secret' => 'a1b2c3d4e5f678901234567890abcdef',
  'ENV' => 'local',   // change to 'live' on production

  'LOCAL_API_BASE' => 'http://localhost/exotic_vendor/api',
  'LOCAL_WS_URL'   => 'ws://localhost:8080',

  'TEST_API_BASE'  => 'https://sellertest.exoticindia.com/api',
  'TEST_WS_URL'    => 'wss://sellertest.exoticindia.com:8080',

  'LIVE_API_BASE'  => 'https://seller.exoticindia.com/api',
  'LIVE_WS_URL'    => 'wss://seller.exoticindia.com:8080',
];