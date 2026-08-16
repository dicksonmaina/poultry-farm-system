<?php
/**
 * M-Pesa Daraja API configuration and client bootstrap.
 *
 * Uses paymentsds/mpesa-php-sdk for STK Push, C2B, B2C,
 * transaction status, and callback verification.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Paymentsds\MPesa\Client;
use Paymentsds\MPesa\Configuration;

function env_mpesa(string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function mpesa_config(): Configuration {
    return Configuration::get([
        'Environment' => env_mpesa('MPESA_ENVIRONMENT', 'sandbox'),
        'AppKey' => env_mpesa('MPESA_APP_KEY', ''),
        'AppSecret' => env_mpesa('MPESA_APP_SECRET', ''),
        'Shortcode' => env_mpesa('MPESA_SHORTCODE', ''),
        'Initiator' => env_mpesa('MPESA_INITIATOR', ''),
        'InitiatorPassword' => env_mpesa('MPESA_INITIATOR_PASSWORD', ''),
    ]);
}

function mpesa_client(): Client {
    static $client = null;

    if ($client === null) {
        $client = new Client(mpesa_config());
    }

    return $client;
}

function mpesa_debug(): bool {
    return env_mpesa('MPESA_DEBUG', '0') === '1';
}
