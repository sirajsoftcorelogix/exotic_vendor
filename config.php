<?php
return [
  'db' => [
    'host' => 'localhost',
    'name' => 'vendor_portal_test',
    'user' => 'vendor_user',
    'pass' => 'eXotic@123',
    'charset' => 'utf8mb4'
  ],
  'token_secret' => 'a1b2c3d4e5f678901234567890abcdef',
  'ENV' => 'test',   // change to 'live' on production

  'LOCAL_API_BASE' => 'http://localhost/exotic_vendor/api',
  'LOCAL_WS_URL'   => 'ws://localhost:8888',

  'TEST_API_BASE'  => 'https://sellertest.exoticindia.com/api',
  'TEST_WS_URL'    => 'wss://sellertest.exoticindia.com/ws/',

  'LIVE_API_BASE'  => 'https://seller.exoticindia.com/api',
  'LIVE_WS_URL'    => 'wss://seller.exoticindia.com/ws/',

  'WS_PORT' => 8888
];