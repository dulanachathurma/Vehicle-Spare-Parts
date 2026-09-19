<?php

declare(strict_types=1);

/**
 * config/stripe.php - Stripe payment gateway configuration.
 *
 * Provides centralized configuration and helper functions for the Stripe PHP SDK.
 * Reads credentials from environment variables first, falling back to constants
 * defined in config/config.local.php (which is git-ignored and per-machine).
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

function getStripePublishableKey(): string
{
    $env = getenv('STRIPE_PUBLISHABLE_KEY');
    if ($env !== false && trim($env) !== '') {
        return trim($env);
    }
    if (defined('STRIPE_PUBLISHABLE_KEY') && trim((string) STRIPE_PUBLISHABLE_KEY) !== '') {
        return trim((string) STRIPE_PUBLISHABLE_KEY);
    }
    return '';
}

function getStripeSecretKey(): string
{
    $env = getenv('STRIPE_SECRET_KEY');
    if ($env !== false && trim($env) !== '') {
        return trim($env);
    }
    if (defined('STRIPE_SECRET_KEY') && trim((string) STRIPE_SECRET_KEY) !== '') {
        return trim((string) STRIPE_SECRET_KEY);
    }
    return '';
}

function getStripeWebhookSecret(): string
{
    $env = getenv('STRIPE_WEBHOOK_SECRET');
    if ($env !== false && trim($env) !== '') {
        return trim($env);
    }
    if (defined('STRIPE_WEBHOOK_SECRET') && trim((string) STRIPE_WEBHOOK_SECRET) !== '') {
        return trim((string) STRIPE_WEBHOOK_SECRET);
    }
    return '';
}

function getStripeCurrency(): string
{
    $env = getenv('STRIPE_CURRENCY');
    if ($env !== false && trim($env) !== '') {
        return strtolower(trim($env));
    }
    if (defined('STRIPE_CURRENCY') && trim((string) STRIPE_CURRENCY) !== '') {
        return strtolower(trim((string) STRIPE_CURRENCY));
    }
    return 'lkr';
}

function isStripeConfigured(): bool
{
    $secret = getStripeSecretKey();
    return $secret !== '' && strpos($secret, 'sk_') === 0 && stripos($secret, 'placeholder') === false;
}


function getStripeClient(): \Stripe\StripeClient
{
    static $client = null;

    if ($client === null) {
        $secretKey = getStripeSecretKey();
        if ($secretKey === '') {
            throw new RuntimeException('Stripe secret key is not configured. Please set STRIPE_SECRET_KEY in config/config.local.php or environment.');
        }
        $client = new \Stripe\StripeClient($secretKey);
    }

    return $client;
}

// Initialize global Stripe API key if secret key is present
$secret = getStripeSecretKey();
if ($secret !== '') {
    \Stripe\Stripe::setApiKey($secret);
}
