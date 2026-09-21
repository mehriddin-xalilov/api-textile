<?php

/*
|--------------------------------------------------------------------------
| To'lov tizimlari (O'zbekiston)
|--------------------------------------------------------------------------
| Payme: Merchant API (JSON-RPC, Basic auth "Paycom:{key}"), checkout: https://checkout.paycom.uz/{base64}
| Click: SHOP-API (prepare/complete, md5 imzo), checkout: https://my.click.uz/services/pay
| Uzum (Apelsin): havola bilan yo'naltirish; server callback shartnomadan keyin.
| Hammasi .env dagi kalitlar bilan; test rejimida sandbox manzillari.
*/

return [
    'callback_base' => env('PAYMENTS_CALLBACK_BASE', env('APP_URL')),
    'return_url' => env('PAYMENTS_RETURN_URL', 'http://localhost:5174/payment/result'),

    'payme' => [
        'merchant_id' => env('PAYME_MERCHANT_ID', ''),
        'key' => env('PAYME_KEY', ''),            // prod kalit
        'test_key' => env('PAYME_TEST_KEY', ''),  // test kalit
        'checkout_url' => env('PAYME_CHECKOUT_URL', 'https://checkout.paycom.uz'),
        'timeout_ms' => 12 * 60 * 60 * 1000,      // tranzaksiya 12 soat ichida bajarilishi kerak
    ],
    'click' => [
        'service_id' => env('CLICK_SERVICE_ID', ''),
        'merchant_id' => env('CLICK_MERCHANT_ID', ''),
        'merchant_user_id' => env('CLICK_MERCHANT_USER_ID', ''),
        'secret_key' => env('CLICK_SECRET_KEY', ''),
        'checkout_url' => env('CLICK_CHECKOUT_URL', 'https://my.click.uz/services/pay'),
    ],
    'uzum' => [
        'service_id' => env('UZUM_SERVICE_ID', ''),
        'checkout_url' => env('UZUM_CHECKOUT_URL', 'https://www.apelsin.uz/open-service'),
    ],
];
