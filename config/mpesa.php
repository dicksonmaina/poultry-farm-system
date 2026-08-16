<?php
/**
 * M-Pesa Daraja API configuration and native cURL client.
 *
 * Uses direct HTTP requests instead of vendor dependencies so this
 * repo can run without Composer.
 */

function env_mpesa(string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function mpesa_base_url(): string {
    $env = env_mpesa('MPESA_ENVIRONMENT', 'sandbox');

    return $env === 'production'
        ? 'https://api.safaricom.co.ke'
        : 'https://sandbox.safaricom.co.ke';
}

function mpesa_get_token(): string {
    $key = env_mpesa('MPESA_APP_KEY', '');
    $secret = env_mpesa('MPESA_APP_SECRET', '');

    if ($key === '' || $secret === '') {
        throw new RuntimeException('MPESA_APP_KEY and MPESA_APP_SECRET are required');
    }

    $url = mpesa_base_url() . '/oauth/v1/generate?grant_type=client_credentials';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Basic ' . base64_encode($key . ':' . $secret)],
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || $response === false) {
        throw new RuntimeException('Failed to get M-Pesa access token');
    }

    $data = json_decode($response, true);
    $accessToken = $data['access_token'] ?? '';

    if ($accessToken === '') {
        throw new RuntimeException('Empty M-Pesa access token');
    }

    return $accessToken;
}

function mpesa_push(array $payload): array {
    $shortcode = env_mpesa('MPESA_SHORTCODE', '');
    $url = mpesa_base_url() . '/mpesa/stkpush/v1/processrequest';

    $body = [
        'BusinessShortCode' => $shortcode,
        'Password' => mpesa_stk_password($shortcode),
        'Timestamp' => gmdate('YmdHis'),
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => (int) $payload['Amount'],
        'PartyA' => (string) $payload['PartyA'],
        'PartyB' => $shortcode,
        'PhoneNumber' => (string) $payload['PhoneNumber'],
        'CallBackURL' => (string) $payload['QueueTimeOutURL'],
        'AccountReference' => (string) $payload['AccountReference'],
        'TransactionDesc' => (string) $payload['TransactionDesc'],
    ];

    $token = mpesa_get_token();

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400 || $response === false) {
        return [
            'ResponseCode' => '1',
            'ResponseDescription' => 'HTTP ' . $httpCode,
            'raw' => $response,
        ];
    }

    return json_decode($response, true) ?: [];
}

function mpesa_stk_password(string $shortcode): string {
    $passkey = env_mpesa('MPESA_PASSKEY', '');
    $timestamp = gmdate('YmdHis');

    if ($passkey === '') {
        return base64_encode($shortcode . '|' . $timestamp);
    }

    return base64_encode($shortcode . $passkey . $timestamp);
}

function mpesa_debug(): bool {
    return env_mpesa('MPESA_DEBUG', '0') === '1';
}
