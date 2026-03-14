<?php

namespace Daniardev\LaravelTsd\Exceptions;

use Daniardev\LaravelTsd\Helpers\AppResponse;
use Daniardev\LaravelTsd\Helpers\AppLog;
use Daniardev\LaravelTsd\Exceptions\AppException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Throwable;

/**
 * Global Exception Handler
 *
 * Standardized exception handling for all Laravel projects using laravel_tsd package.
 * Copy this file to app/Exceptions/Handler.php in your project.
 *
 * @package Daniardev\LaravelTsd\Exceptions
 */
class LaravelTsdHandler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * Implements best practices:
     * - Proper log levels (warning for 401/403, error for 500+)
     * - Structured context with request ID, user context
     * - Sanitized traces (no sensitive data)
     * - Web requests: NO logging (clean)
     * - API requests: Selective logging based on severity
     * - Stops propagation to prevent duplicate logging
     * - Uses json-daily channel explicitly for consistent formatting
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            $request = request();

            // Don't log web request errors at all
            if (!$request || !$request->expectsJson()) {
                return false; // Stop propagation
            }

            // Skip normal API errors (404, 422, etc)
            if ($this->shouldNotLogInApi($e)) {
                return false; // Stop propagation
            }

            // Build context with request ID and user info
            $context = $this->buildLogContext($request, $e);

            // Use json-daily channel explicitly to ensure proper formatting
            $logger = app('log')->channel('json-daily');

            // Determine log level based on exception type
            if ($this->isSecurityIssue($e)) {
                // 401/403 - Security monitoring (WARNING level)
                $logger->warning('API security issue detected', $context);
            } else {
                // 500+ - System error (ERROR level)
                $logger->error('API system error detected', $context);
            }

            return false; // Stop propagation to prevent duplicate logging
        });
    }

    /**
     * Build structured log context with request ID, user info, and sanitized data
     *
     * Uses AppLog::getRequestContext() as base for consistent format across app.
     * Adds exception-specific context (type, message, file, line, trace).
     *
     * @return array Structured context for logging
     */
    private function buildLogContext(?Request $request, Throwable $e): array
    {
        // Start with exception context
        $exceptionContext = [
            'exception_type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        // Add sanitized trace in debug mode only
        if (config('app.debug')) {
            $exceptionContext['trace'] = AppLog::sanitizeTrace($e->getTraceAsString());
        }

        // Merge with AppLog::getRequestContext() for consistent format
        return AppLog::getRequestContext($request, $exceptionContext);
    }

    /**
     * Check if exception is a security issue (401/403)
     *
     * Checks for:
     * - Laravel standard authentication/authorization exceptions
     * - Laravel Passport OAuth2 exceptions
     */
    private function isSecurityIssue(Throwable $e): bool
    {
        // Laravel standard exceptions
        if ($e instanceof AuthenticationException || $e instanceof AuthorizationException) {
            return true;
        }

        // Laravel Passport / OAuth2 exceptions
        $oauthExceptionClass = 'League\OAuth2\Server\Exception\OAuthServerException';
        if ($e instanceof $oauthExceptionClass) {
            return true;
        }

        return false;
    }

    /**
     * Determine if API exception should NOT be logged
     *
     * NOT logged (normal/expected):
     * - 404: Wrong endpoint or outdated API docs
     * - 422: User submitted invalid data (expected validation behavior)
     * - 429: Rate limiting (working as intended)
     * - 419: CSRF mismatch (normal session expiry)
     * - 405: Wrong HTTP method (API usage error)
     * - 413: File too large (validation error)
     *
     * LOGGED (suspicious/system errors):
     * - 401/403: Unauthorized access attempts (security monitoring)
     * - 500+: Server errors, database errors, runtime exceptions
     */
    private function shouldNotLogInApi(Throwable $e): bool
    {
        $normalApiErrors = [
            NotFoundHttpException::class,              // 404 - Normal API usage error
            ModelNotFoundException::class,             // 404 - Normal API usage error
            ValidationException::class,                 // 422 - Expected validation behavior
            ThrottleRequestsException::class,           // 429 - Rate limiting working
            MethodNotAllowedHttpException::class,       // 405 - API usage error
            TokenMismatchException::class,              // 419 - Normal session expiry
            PostTooLargeException::class,               // 413 - Validation error
        ];

        return in_array(get_class($e), $normalApiErrors);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param $request
     * @param Throwable $e
     * @return Response|JsonResponse|RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $e): Response|JsonResponse|RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        // For non-API (web) requests, use Laravel's default rendering (HTML pages)
        if (!$request->expectsJson()) {
            return parent::render($request, $e);
        }

        // For API requests, return JSON responses below

        // exception authentication - 401
        if ($e instanceof AuthenticationException) {
            return AppResponse::error(
                __('tsd_message.unauthenticated'),
                401
            );
        }

        // exception authorization - 403
        if ($e instanceof AuthorizationException) {
            return AppResponse::error(
                __('tsd_message.forbidden'),
                403
            );
        }

        // exception not found (route) - 404
        if ($e instanceof NotFoundHttpException) {
            return AppResponse::error(
                __('tsd_message.notFound'),
            );
        }

        // exception model not found - 404
        if ($e instanceof ModelNotFoundException) {
            return AppResponse::error(
                __('tsd_message.emptyLoadedName', ['name' => class_basename($e->getModel())]),
            );
        }

        // exception method not allowed - 405
        if ($e instanceof MethodNotAllowedHttpException) {
            return AppResponse::error(
                __('tsd_message.methodNotAllowed'),
                405
            );
        }

        // exception post too large - 413
        if ($e instanceof PostTooLargeException) {
            return AppResponse::error(
                __('tsd_message.fileTooLarge'),
                413
            );
        }

        // exception validation - 422
        if ($e instanceof ValidationException) {
            return AppResponse::error(
                $e->getMessage(),
                422
            );
        }

        // exception token mismatch (CSRF) - 419
        if ($e instanceof TokenMismatchException) {
            return AppResponse::error(
                __('tsd_message.sessionExpired'),
                419
            );
        }

        // exception too many requests - 429
        if ($e instanceof ThrottleRequestsException) {
            return AppResponse::error(
                __('tsd_message.tooManyRequests'),
                429
            );
        }

        // exception internal (custom AppException)
        if ($e instanceof AppException) {
            return AppResponse::error(
                $e->message(),
                $e->code()
            );
        }

        // exception query (database error)
        if ($e instanceof QueryException) {
            return AppResponse::error(
                config('app.debug')
                    ? __('tsd_message.databaseError') . ': ' . $e->getMessage()
                    : __('tsd_message.databaseError'),
                500
            );
        }

        // exception other (generic Exception, etc)
        return AppResponse::error(
            config('app.debug') ? $e->getMessage() : __('tsd_message.serverError'),
            500
        );
    }
}