<?php

return [
    'tranportal_id'       => env('KNET_TRANPORTAL_ID'),
    'tranportal_password' => env('KNET_TRANPORTAL_PASSWORD'),
    'resource_key'        => env('KNET_RESOURCE_KEY'),
    'sandbox'             => env('KNET_SANDBOX', true),
    'test_url'            => env('KNET_TEST_PAYMENT_API_URL'),
    'live_url'            => env('KNET_LIVE_PAYMENT_API_URL'),
    'currency_code'       => env('KNET_CURRENCY_CODE', '414'),
    'language'            => env('KNET_LANGUAGE', 'USA'),
];