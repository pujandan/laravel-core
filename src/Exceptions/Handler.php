<?php

namespace DaniarDev\LaravelCore\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Throwable;

/**
 * Base Exception Handler for Laravel Applications
 *
 * This is a reference implementation that demonstrates best practices
 * for exception handling in Laravel applications. Extend this class in
 * your application's Handler class.
 *
 * Features:
 * - Structured logging with request context
 * - Different handling for API vs Web requests
 * - Proper log levels (warning for security issues, error for system errors)
 * - Sanitized traces in debug mode
 * - Comprehensive exception type handling
 */
class Handler extends ExceptionHandler
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
     * Best practices:
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
            // Uncomment if you want to skip web logging:
            // if (!$request || !$request->expectsJson()) {
            //     return false; // Stop propagation
            // }

            // Skip normal API errors (404, 422, etc)
            if ($this->shouldNotLogInApi($e)) {
                return false; // Stop propagation
            }

            // Build context with request ID and user info
            $context = $this->buildLogContext($request, $e);

            // Use json-daily channel explicitly to ensure proper formatting
            // Create logger directly from config to bypass facade issues
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
     * This method should work with your AppLog helper if available.
     * Otherwise, implement your own context building logic.
     *
     * @param Request|null $request
     * @param Throwable $e
     * @return array Structured context for logging
     */
    protected function buildLogContext(?Request $request, Throwable $e): array
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
            // If you have AppLog::sanitizeTrace(), use it here
            // $exceptionContext['trace'] = AppLog::sanitizeTrace($e->getTraceAsString());
            $exceptionContext['trace'] = $this->sanitizeTrace($e->getTraceAsString());
        }

        // Add request context
        if ($request) {
            $exceptionContext['request_id'] = $request->header('X-Request-Id') ?: (string) Str::uuid();
            $exceptionContext['user_id'] = auth()->id();
            $exceptionContext['url'] = $request->fullUrl();
            $exceptionContext['method'] = $request->method();
            $exceptionContext['ip'] = $request->ip();
        }

        return $exceptionContext;
    }

    /**
     * Sanitize stack trace to remove sensitive data
     *
     * @param string $trace
     * @return string
     */
    protected function sanitizeTrace(string $trace): string
    {
        // Remove potential passwords, tokens, API keys from trace
        $patterns = [
            '/password=["\']([^"\']*)["\']/',
            '/token=["\']([^"\']*)["\']/',
            '/api_key=["\']([^"\']*)["\']/',
            '/secret=["\']([^"\']*)["\']/',
        ];

        return preg_replace($patterns, '$1=[REDACTED]', $trace);
    }

    /**
     * Check if exception is a security issue (401/403)
     *
     * @param Throwable $e
     * @return bool
     */
    protected function isSecurityIssue(Throwable $e): bool
    {
        // Laravel standard exceptions
        if ($e instanceof AuthenticationException || $e instanceof AuthorizationException) {
            return true;
        }

        // Laravel Passport / OAuth2 exceptions
        $oauthExceptionClass = 'League\OAuth2\Server\Exception\OAuthServerException';
        if (class_exists($oauthExceptionClass) && $e instanceof $oauthExceptionClass) {
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
     *
     * @param Throwable $e
     * @return bool
     */
    protected function shouldNotLogInApi(Throwable $e): bool
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
     * @param Request $request
     * @param Throwable $e
     * @return Response|JsonResponse|RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $e): Response|JsonResponse|RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        // For non-API (web) requests, use Laravel's default rendering (HTML pages)
        // This ensures web routes get proper 404, 403, 500 HTML pages
        if (!$request->expectsJson()) {
            return parent::render($request, $e);
        }

        // For API requests, return JSON responses below
        // Note: Replace AppResponse with your preferred response helper

        // exception authentication - 401
        if ($e instanceof AuthenticationException) {
            return $this->apiError(
                'Unauthenticated',
                401
            );
        }

        // exception authorization - 403
        if ($e instanceof AuthorizationException) {
            return $this->apiError(
                'Forbidden',
                403
            );
        }

        // exception not found (route) - 404
        if ($e instanceof NotFoundHttpException) {
            return $this->apiError(
                'Resource not found',
                404
            );
        }

        // exception model not found - 404
        if ($e instanceof ModelNotFoundException) {
            return $this->apiError(
                class_basename($e->getModel()) . ' not found',
                404
            );
        }

        // exception method not allowed - 405
        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->apiError(
                'Method not allowed',
                405
            );
        }

        // exception post too large - 413
        if ($e instanceof PostTooLargeException) {
            return $this->apiError(
                'File too large',
                413
            );
        }

        // exception validation - 422
        if ($e instanceof ValidationException) {
            return $this->apiError(
                $e->getMessage(),
                422
            );
        }

        // exception token mismatch (CSRF) - 419
        if ($e instanceof TokenMismatchException) {
            return $this->apiError(
                'Session expired',
                419
            );
        }

        // exception too many requests - 429
        if ($e instanceof ThrottleRequestsException) {
            return $this->apiError(
                'Too many requests',
                429
            );
        }

        // exception internal (custom AppException)
        if ($e instanceof AppException) {
            return $this->apiError(
                $e->message(),
                $e->code(),
                $e->getData(),
                $e->getErrorCode()
            );
        }

        // exception query (database error)
        if ($e instanceof QueryException) {
            return $this->apiError(
                config('app.debug')
                    ? 'Database error: ' . $e->getMessage()
                    : 'Database error',
                500
            );
        }

        // exception other (generic Exception, etc)
        return $this->apiError(
            config('app.debug') ? $e->getMessage() : 'Server error',
            500
        );
    }

    /**
     * Return a standardized API error response
     *
     * Override this method to use your preferred response format
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $data
     * @param string|null $errorCode
     * @return JsonResponse
     */
    protected function apiError(
        string $message,
        int $statusCode = 400,
        $data = null,
        ?string $errorCode = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'data' => $data,
        ], $statusCode);
    }
}
