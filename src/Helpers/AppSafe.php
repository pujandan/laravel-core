<?php

namespace Daniardev\LaravelTsd\Helpers;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AppSafe - Safe Execution Helper
 *
 * Provides safe execution wrapper for operations that should fail silently.
 * Ideal for non-critical side effects like: emails, notifications, webhooks, cache updates.
 *
 * @package Daniardev\LaravelTsd\Helpers
 *
 */
class AppSafe
{
    /**
     * Execute callback safely with silent failure
     *
     * Catches all exceptions/logs, logs them, and continues execution.
     * Use for operations that should NOT break the main flow.
     *
     * @param string $tag Log tag for identification (e.g., "Email applicant")
     * @param callable $callback Function to execute
     * @param mixed ...$params Parameters to pass to callback
     * @return mixed|null Return value from callback, or null if failed
     *
     * @example
     * AppSafe::run('Send welcome email', function($user) {
     *     return Mail::to($user->email)->send(new WelcomeEmail($user));
     * }, $user);
     */
    public static function run(string $tag, callable $callback, ...$params): mixed
    {
        try {
            return $callback(...$params);
        } catch (Throwable $e) {
            self::logFailure($tag, $e);
            return null;
        }
    }

    /**
     * Execute callback safely with custom log level
     *
     * @param string $tag Log tag for identification
     * @param string $level Log level: debug, info, warning, error, critical
     * @param callable $callback Function to execute
     * @param mixed ...$params Parameters to pass to callback
     * @return mixed|null Return value from callback, or null if failed
     *
     * @example
     * AppSafe::runWithLevel('Office email', 'error', fn() =>
     *     $this->emailService->send(...)
     * );
     */
    public static function runWithLevel(string $tag, string $level, callable $callback, ...$params): mixed
    {
        try {
            return $callback(...$params);
        } catch (Throwable $e) {
            self::logFailure($tag, $e, $level);
            return null;
        }
    }

    /**
     * Execute callback safely with configurable silence
     *
     * @param string $tag Log tag for identification
     * @param callable $callback Function to execute
     * @param bool $silent If false, will re-throw after logging
     * @param mixed ...$params Parameters to pass to callback
     * @return mixed|null Return value from callback, or null if failed
     * @throws Throwable If silent=false and exception occurs
     *
     * @example
     * // Silent (default)
     * AppSafe::runMaybe('Non-critical email', $callback, silent: true);
     *
     * // Will throw after logging
     * AppSafe::runMaybe('Critical email', $callback, silent: false);
     */
    public static function runMaybe(string $tag, callable $callback, bool $silent = true, ...$params): mixed
    {
        try {
            return $callback(...$params);
        } catch (Throwable $e) {
            self::logFailure($tag, $e);

            if (!$silent) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * Execute callback safely with retry mechanism
     *
     * @param string $tag Log tag for identification
     * @param callable $callback Function to execute
     * @param int $maxAttempts Maximum retry attempts (default: 3)
     * @param array $backoff Delay in seconds for each attempt (default: [1, 2, 4])
     * @param mixed ...$params Parameters to pass to callback
     * @return mixed|null Return value from callback, or null if all attempts failed
     *
     * @example
     * AppSafe::runWithRetry('External API call', $callback, maxAttempts: 3);
     */
    public static function runWithRetry(
        string $tag,
        callable $callback,
        int $maxAttempts = 3,
        array $backoff = [1, 2, 4],
        ...$params
    ): mixed {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $result = $callback(...$params);

                // Log successful retry if not first attempt
                if ($attempt > 1) {
                    Log::info("SafeRun retry succeeded: {$tag}", [
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                    ]);
                }

                return $result;
            } catch (Throwable $e) {
                $lastException = $e;

                // Log retry attempt
                Log::warning("SafeRun retry attempt {$attempt}/{$maxAttempts} failed: {$tag}", [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);

                // Don't sleep after last attempt
                if ($attempt < $maxAttempts) {
                    $delay = $backoff[$attempt - 1] ?? pow(2, $attempt - 1);
                    sleep($delay);
                }
            }
        }

        // All attempts failed
        self::logFailure($tag, $lastException, 'error', [
            'attempts' => $maxAttempts,
            'retry_failed' => true,
        ]);

        return null;
    }

    /**
     * Log failure with structured context (matches Handler.php format)
     *
     * @param string $tag Log tag
     * @param Throwable $e Exception
     * @param string $level Log level (default: warning)
     * @param array $extraContext Additional context to log
     * @return void
     */
    private static function logFailure(
        string $tag,
        Throwable $e,
        string $level = 'warning',
        array $extraContext = []
    ): void {
        // Build base context (matches Handler.php format)
        $context = [
            'tag' => $tag,
            'exception_type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        // Merge extra context
        $context = array_merge($context, $extraContext);

        // Add request context if available
        if (app()->runningInConsole()) {
            // Console command
            $context['command'] = $_SERVER['argv'] ?? [];
        } else {
            // HTTP request
            $request = request();
            if ($request) {
                // Add request ID (matches Handler.php)
                $context['request_id'] = $request->attributes->get('request_id', 'N/A');

                // Add request metadata (matches Handler.php format)
                $context['request'] = [
                    'method' => $request->method(),
                    'url' => AppLog::sanitizeUrl($request->fullUrl()),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ];

                // Add user context if authenticated (matches Handler.php)
                $context['user'] = AppLog::getUserContext($request);
            }
        }

        // Add sanitized trace in debug mode only (matches Handler.php)
        if (config('app.debug')) {
            $context['trace'] = AppLog::sanitizeTrace($e->getTraceAsString());
        }

        // Use json-daily channel for structured logging (matches Handler.php)
        Log::channel('json-daily')->$level("SafeRun failed: {$tag}", $context);
    }
}
