<?php
declare(strict_types=1);

/**
 * Copy this file to config.local.php and fill in the values for your
 * own machine. config.local.php is git-ignored (see .gitignore) and
 * must NEVER be committed - it is the only place real credentials live.
 */

// --- Database ---------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'vspms_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- PayHere sandbox (Module 3) ----------------------------------------
// Sign up free at https://sandbox.payhere.lk/account/signup/createaccount
// then copy the Merchant ID and Merchant Secret from Integrations.
define('PAYHERE_MERCHANT_ID', 'your-sandbox-merchant-id');
define('PAYHERE_MERCHANT_SECRET', 'your-sandbox-merchant-secret');

// --- Stripe Payment Gateway (Test Mode) --------------------------------
// Get your test API keys from https://dashboard.stripe.com/test/apikeys
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_publishable_key_here');
define('STRIPE_SECRET_KEY', 'sk_test_your_secret_key_here');
// Get your webhook signing secret from Stripe CLI or Dashboard Webhooks
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_webhook_signing_secret_here');
define('STRIPE_CURRENCY', 'lkr'); // Default currency (e.g. lkr or usd)
