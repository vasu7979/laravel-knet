
<?php

namespace SmartLInks\LaravelKnet\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class KnetService
{
    
    public function __construct(
        private array $config,
        private string $baseUrl,
        private string $resourceKey
    ) {
        $this->resourceKey = $this->config['resource_key'] ?? '';

        $baseUrl = ($this->config['sandbox'] ?? true)
            ? ($this->config['test_url'] ?? '')
            : ($this->config['live_url'] ?? '');

        if (empty($baseUrl)) {
            throw new Exception('KNET payment gateway URL is not configured.');
        }

        $this->baseUrl = rtrim($baseUrl, '/') . '/';
    }

    /**
     * Create KNET payment URL.
     * 
     * @param float $amount The payment amount.
     * @param string $trackId The unique track ID for the transaction.
     * @param string $responseUrl The URL to redirect after successful payment.
     * @param string $errorUrl The URL to redirect after failed payment.
     * @param array $udfs Optional user-defined fields (udf1 to udf5).
     * 
     * @return string The generated KNET payment URL.
     */
    public function createPaymentUrl(
        float $amount,
        string $trackId,
        string $responseUrl,
        string $errorUrl,
        array $udfs = []
    ): string {
        $params = [
            'id'           => $this->config['tranportal_id'] ?? '',
            'password'     => $this->config['tranportal_password'] ?? '',
            'action'       => '1',
            'langid'       => $this->config['language'] ?? 'USA',
            'currencycode' => $this->config['currency_code'] ?? '414',
            'amt'          => number_format($amount, 3, '.', ''),
            'responseURL'  => $responseUrl,
            'errorURL'     => $errorUrl,
            'trackid'      => $trackId,
            'udf1'         => $udfs['udf1'] ?? '',
            'udf2'         => $udfs['udf2'] ?? '',
            'udf3'         => $udfs['udf3'] ?? '',
            'udf4'         => $udfs['udf4'] ?? '',
            'udf5'         => $udfs['udf5'] ?? '',
        ];

        foreach (range(1, 5) as $i) {
            $params["udf{$i}"] = $udfs["udf{$i}"] ?? '';
        }

        $rawQuery = http_build_query($params);

        $encryptedTrandata = $this->encryptAES(
            $rawQuery,
            $this->resourceKey
        );

        $redirectQuery = http_build_query([
            'param'        => 'paymentInit',
            'trandata'     => $encryptedTrandata,
            'tranportalId' => $this->config['tranportal_id'] ?? '',
            'responseURL'  => $responseUrl,
            'errorURL'     => $errorUrl,
        ]);

        return $this->baseUrl . 'PaymentHTTP.htm?' . $redirectQuery;
    }

    /**
     * Decrypt KNET callback response.
     * 
     * @param string $encryptedTrandata The encrypted trandata from KNET callback.
     * 
     * @return array The decrypted response data as an associative array.
     */
    public function decryptCallback(string $encryptedTrandata): array
    {
        $decryptedString = $this->decryptAES(
            $encryptedTrandata,
            $this->resourceKey
        );

        parse_str($decryptedString, $result);

        return $result;
    }

    /**
     * Send transaction request using Laravel HTTP client.
     * 
     * @param string $xmlPayload The XML payload to send.
     * 
     * @return string The response body from the KNET API.
     */
    public function sendTransaction(string $xmlPayload): string
    {
        $url = $this->baseUrl . 'tranPipe.htm?param=tranInit';

        $response = Http::timeout(30)
            ->connectTimeout(10)
            ->withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->withBody(
                $xmlPayload,
                'application/x-www-form-urlencoded'
            )
            ->post($url);

        $response->throw();

        return $response->body();
    }

    /**
     * AES encryption.
     *
     * @param string $str The string to encrypt.
     * @param string $key The encryption key.
     *
     * @return string The encrypted string.
     */
    private function encryptAES(string $str, string $key): string
    {
        $blockSize = 16;
        $pad = $blockSize - (strlen($str) % $blockSize);

        $str .= str_repeat(chr($pad), $pad);

        $encrypted = openssl_encrypt(
            $str,
            'AES-128-CBC',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $key
        );

        if ($encrypted === false) {
            throw new Exception('KNET encryption failed.');
        }

        return strtoupper(bin2hex($encrypted));
    }

    /**
     * AES decryption.
     * 
     * @param string $code The encrypted string to decrypt.
     * @param string $key The decryption key.
     * 
     * @return string The decrypted string.
     */
    private function decryptAES(string $code, string $key): string
    {
        $binaryData = hex2bin($code);

        if (
            $binaryData === false ||
            strlen($binaryData) === 0 ||
            strlen($binaryData) % 16 !== 0
        ) {
            throw new Exception('Invalid KNET encrypted data.');
        }

        $decrypted = openssl_decrypt(
            $binaryData,
            'AES-128-CBC',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $key
        );

        if ($decrypted === false || $decrypted === '') {
            throw new Exception('KNET decryption failed.');
        }

        $pad = ord($decrypted[strlen($decrypted) - 1]);

        if ($pad < 1 || $pad > 16) {
            throw new Exception('Invalid AES padding.');
        }

        $padding = substr($decrypted, -$pad);

        if ($padding !== str_repeat(chr($pad), $pad)) {
            throw new Exception('Invalid AES padding.');
        }

        return substr($decrypted, 0, -$pad);
    }
}
