<?php
return [
  'db' => [
    'host' => 'localhost',
    'name' => 'exotic_vendor_portal',
    'user' => 'vendor_user',
    'pass' => 'eXotic@123',
    'charset' => 'utf8mb4'
  ],
  'token_secret' => 'a1b2c3d4e5f678901234567890abcdef',
  'ENV' => 'live',   // change to 'live' on production

  'TEST_API_BASE'  => 'https://sellertest.exoticindia.com/api',
  'TEST_WS_URL'    => 'wss://sellertest.exoticindia.com/ws/',

  'LIVE_API_BASE'  => 'https://seller.exoticindia.com/api',
  'LIVE_WS_URL'    => 'wss://seller.exoticindia.com/ws/',

  'WS_PORT' => 8082
];
