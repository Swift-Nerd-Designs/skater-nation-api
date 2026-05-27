<?php

namespace App\Infrastructure\Services;

use Sentry\SentrySdk;
use Throwable;

/**
 * Thin wrapper around the Sentry PHP SDK.
 *
 * Initialised once during bootstrap (see Config/Events.php pre_system hook).
 * Provides a static capture helper so any layer can report exceptions without
 * importing the Sentry SDK directly.
 */
class SentryService
{
    private static bool $initialised = false;

    /**
     * Initialise Sentry from environment. Safe to call multiple times — only
     * runs on the first call. Skipped when SENTRY_DSN is empty or in testing.
     */
    public static function init(): void
    {
        if (self::$initialised) {
            return;
        }

        $dsn = env('SENTRY_DSN', '');
        if ($dsn === '' || ENVIRONMENT === 'testing') {
            self::$initialised = true;
            return;
        }

        \Sentry\init([
            'dsn'                => $dsn,
            'environment'        => ENVIRONMENT,
            'traces_sample_rate' => ENVIRONMENT === 'production' ? 0.1 : 0.0,
            'release'            => env('APP_VERSION', 'unknown'),
            // Exclude E_DEPRECATED (8192) and E_USER_DEPRECATED (16384) — vendor
            // libraries (dompdf, etc.) emit these on PHP 8.x and they are noise.
            'error_types'        => E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED,
        ]);

        self::$initialised = true;
    }

    /**
     * Capture an exception and send it to Sentry.
     * No-ops gracefully when Sentry is not configured.
     */
    public static function capture(Throwable $e): void
    {
        if (! self::$initialised || SentrySdk::getCurrentHub()->getClient() === null) {
            return;
        }

        \Sentry\captureException($e);
    }
}
