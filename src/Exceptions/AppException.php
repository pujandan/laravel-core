<?php

namespace DaniarDev\LaravelCore\Exceptions;

use Exception;
use JetBrains\PhpStorm\NoReturn;

class AppException extends Exception
{
    /**
     * Error data
     *
     * @var mixed
     */
    protected $data;

    /**
     * Error code
     *
     * @var string|null
     */
    protected $errorCode;

    /**
     * Error message
     *
     * @var string|null
     */
    protected $message;

    /**
     * HTTP status code
     *
     * @var int
     */
    protected $code;

    /**
     * Create a new exception instance
     *
     * @param string|null $message
     * @param int $code
     * @param mixed $data
     * @param string|null $errorCode
     * @param Exception|null $previous
     */
    public function __construct(
        ?string $message = null,
        int $code = 404,
        $data = null,
        ?string $errorCode = null,
        Exception $previous = null
    ) {
        parent::__construct($message ?? 'An error occurred', $code, $previous);
        $this->message = $message;
        $this->code = $code;
        $this->data = $data;
        $this->errorCode = $errorCode;
    }

    /**
     * Get error message
     *
     * @return string|null
     */
    public function message(): ?string
    {
        return $this->message ?? $this->getMessage();
    }

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * Get error data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get error code
     *
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Debug and die - dump data as JSON response and exit
     *
     * @param mixed $data
     * @param int $status
     * @return void
     */
    #[NoReturn]
    public static function dd($data, int $status = 200): void
    {
        response()
            ->json($data, $status)
            ->send();

        exit;
    }

    /**
     * Render the exception as an HTTP response
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'data' => $this->data,
        ], $this->code);
    }
}
