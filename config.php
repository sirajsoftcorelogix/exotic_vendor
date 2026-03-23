<?php
return [
  'db' => [
    'host' => '127.0.0.1',
    //'name' => 'vendor_portal_test',
    'name' => 'exotic_vendor_portal',
    'user' => 'vendor_user',
    'pass' => 'eXotic@123',
    'port' => 3306,
    'charset' => 'utf8mb4'
  ],
  'token_secret' => 'a1b2c3d4e5f678901234567890abcdef',
  'ENV' => 'live',   // change to 'live' on production

  'LOCAL_API_BASE' => 'http://localhost/exotic_vendor/api',
  'LOCAL_WS_URL'   => 'ws://localhost:8888',

  'TEST_API_BASE'  => 'https://sellertest.exoticindia.com/api',
  'TEST_WS_URL'    => 'wss://sellertest.exoticindia.com/ws/',

  'LIVE_API_BASE'  => 'https://seller.exoticindia.com/api',
  'LIVE_WS_URL'    => 'wss://seller.exoticindia.com/ws/',

  'WS_PORT' => 8082
  //'WS_PORT' => 8081

  /**
   * Optional: allow running scripts/backfill_published_inbound_logs.php in the browser.
   * Set to a long random string, then call:
   *   .../scripts/backfill_published_inbound_logs.php?key=YOUR_STRING
   *   .../scripts/backfill_published_inbound_logs.php?key=YOUR_STRING&execute=1
   * Leave empty to disable web access (use CLI only).
   */
  'backfill_logs_web_key' => '',

 ];
