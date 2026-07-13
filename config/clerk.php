<?php
/**
 * Clerk configuration.
 * Loaded by includes/bootstrap.php — jangan require koneksi.php di sini.
 */

if (!function_exists('envValue')) {
    function envValue($key, $default = '')
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        return $default;
    }
}

/**
 * Cek apakah Clerk sudah dikonfigurasi (publishable key tersedia).
 */
if (!function_exists('isClerkConfigured')) {
    function isClerkConfigured(): bool
    {
        $key = envValue('CLERK_PUBLISHABLE_KEY');
        return !empty($key) && strpos($key, 'pk_') === 0;
    }
}

return [

    'publishable_key' => envValue('CLERK_PUBLISHABLE_KEY'),

    'secret_key' => envValue('CLERK_SECRET_KEY'),

    'webhook_secret' => envValue('CLERK_WEBHOOK_SECRET'),

];