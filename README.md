
# Laravel KNET Payment Gateway

A Laravel package for integrating the KNET payment gateway into Laravel applications.

Supports payment URL generation, AES encryption/decryption, transaction requests, and payment callback handling.

## Features

- KNET payment URL generation
- AES-128-CBC encryption
- KNET callback decryption
- Transaction requests using Laravel HTTP Client
- Sandbox and production environment support
- Configurable currency and language
- Laravel Facade support
- Laravel configuration publishing

## Requirements

- PHP >= 8.1
- Laravel 10, 11, or 12
- OpenSSL PHP extension
- cURL PHP extension (required by Laravel HTTP client)

## Installation

Install the package using Composer:

```bash
composer require smartlinks/laravel-knet
```

Laravel package auto-discovery registers the service provider automatically.

## Publish Configuration

Run:

```bash
php artisan vendor:publish --tag=knet-config
```

This publishes the package configuration to:

```text
config/knet.php
```

## Environment Configuration

Add the following variables to your `.env` file:

```env
KNET_TRANPORTAL_ID=your_tranportal_id
KNET_TRANPORTAL_PASSWORD=your_tranportal_password
KNET_RESOURCE_KEY=your_resource_key

KNET_SANDBOX=true

KNET_TEST_PAYMENT_API_URL=https://your-test-gateway-url/
KNET_LIVE_PAYMENT_API_URL=https://your-live-gateway-url/

KNET_CURRENCY_CODE=414
KNET_LANGUAGE=USA
```

### Configuration Options

| Configuration | Description | Default |
|---|---|---|
| tranportal_id | KNET Tranportal ID | null |
| tranportal_password | KNET Tranportal Password | null |
| resource_key | KNET encryption resource key | null |
| sandbox | Enable sandbox environment | true |
| test_url | Sandbox gateway URL | null |
| live_url | Production gateway URL | null |
| currency_code | Currency code (414 = KWD) | 414 |
| language | Payment language (USA or AR) | USA |

## Usage

Import the KNET Facade:

```php
use SmartLInks\LaravelKnet\Facades\Knet;
```

### 1. Create Payment URL

```php
$paymentUrl = Knet::createPaymentUrl(
    amount: 10.500,
    trackId: 'ORDER123',
    responseUrl: route('knet.success'),
    errorUrl: route('knet.error')
);

return redirect()->away($paymentUrl);
```

### 2. Create Payment URL with UDF Parameters

```php
$paymentUrl = Knet::createPaymentUrl(
    amount: 10.500,
    trackId: 'ORDER123',
    responseUrl: route('knet.success'),
    errorUrl: route('knet.error'),
    udfs: [
        'udf1' => 'CUSTOMER123',
        'udf2' => 'ORDER123',
        'udf3' => 'INVOICE123',
        'udf4' => '',
        'udf5' => '',
    ]
);
```

### 3. Decrypt Payment Callback

```php
$result = Knet::decryptCallback(
    $request->input('trandata')
);
```

The decrypted response contains the transaction information returned by KNET.

Always validate the transaction status, amount, currency, and order reference before marking an order as paid.

### 4. Send Transaction Request

```php
$response = Knet::sendTransaction($xmlPayload);
```

The method sends the transaction payload to the configured KNET gateway and returns the response body.

## Example Routes

In `routes/web.php`:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use SmartLInks\LaravelKnet\Facades\Knet;

Route::get('/knet/success', function (Request $request) {

    $result = Knet::decryptCallback(
        $request->input('trandata')
    );

    // Validate transaction details before updating order status.

    return response()->json($result);
})->name('knet.success');

Route::get('/knet/error', function (Request $request) {

    return response()->json([
        'status' => 'failed',
        'response' => $request->all(),
    ]);
})->name('knet.error');
```

Ensure your actual KNET callback format matches the request handling shown above.

## Testing

You can use Laravel's HTTP testing tools to mock gateway responses.

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*' => Http::response('SUCCESS', 200),
]);
```

## Security

- Never commit KNET credentials to GitHub.
- Use HTTPS for payment callback URLs.
- Validate all payment callback information.
- Verify transaction status directly with KNET where required.
- Do not mark orders as paid based only on a browser redirect.
- Keep sandbox and production credentials separate.

## License

This package is open-sourced software licensed under the MIT license.

See the LICENSE file for details.

## Disclaimer

This package is an independent Laravel integration library and is not an official KNET or Kuwait International Bank product.

KNET gateway access and merchant credentials must be obtained from the authorized payment provider.

The merchant is responsible for validating payment responses and complying with gateway integration requirements.
