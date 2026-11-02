<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders every error in the one NGP shape (ngp-laravel §5):
 *
 *     { "message": "...", "code": "validation_failed", "errors": { "email": ["..."] } }
 *
 * "message" is translated, "code" is stable, "errors" only appears on 422.
 * Registered in bootstrap/app.php. Laravel has already turned
 * ModelNotFoundException into a 404 and AuthorizationException into a 403
 * by the time an exception gets here.
 */
final class ApiExceptionRenderer
{
    /**
     * Codes for the HTTP errors the framework throws. Other 4xx statuses get
     * "http_error"; other 5xx statuses get "server_error".
     */
    private const HTTP_ERROR_CODES = [
        Response::HTTP_BAD_REQUEST => 'bad_request',
        Response::HTTP_UNAUTHORIZED => 'unauthenticated',
        Response::HTTP_FORBIDDEN => 'forbidden',
        Response::HTTP_NOT_FOUND => 'not_found',
        Response::HTTP_METHOD_NOT_ALLOWED => 'method_not_allowed',
        Response::HTTP_CONFLICT => 'conflict',
        419 => 'csrf_token_mismatch',
        Response::HTTP_TOO_MANY_REQUESTS => 'too_many_requests',
        Response::HTTP_SERVICE_UNAVAILABLE => 'service_unavailable',
    ];

    public function __construct(
        private readonly Application $app,
        private readonly Translator $translator,
    ) {}

    public function render(Throwable $exception): ?JsonResponse
    {
        return match (true) {
            // A response built on purpose, e.g. abort(response(...)), is sent unchanged.
            $exception instanceof HttpResponseException => null,
            $exception instanceof ApiException => $this->respond(
                $exception->status(),
                $exception->errorCode(),
                $exception->getMessage(),
                $exception->errors(),
            ),
            $exception instanceof ValidationException => $this->respond(
                $exception->status,
                'validation_failed',
                $this->translate('errors.validation_failed'),
                $exception->errors(),
            ),
            $exception instanceof AuthenticationException => $this->respond(
                Response::HTTP_UNAUTHORIZED,
                'unauthenticated',
                $this->translate('errors.unauthenticated'),
            ),
            $exception instanceof HttpExceptionInterface => $this->fromHttpException($exception),
            default => $this->serverError(Response::HTTP_INTERNAL_SERVER_ERROR, $exception),
        };
    }

    private function fromHttpException(HttpExceptionInterface $exception): JsonResponse
    {
        $status = $exception->getStatusCode();
        $code = self::HTTP_ERROR_CODES[$status] ?? null;

        if ($code === null && $status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            return $this->serverError($status, $exception, $exception->getHeaders());
        }

        $code ??= 'http_error';
        $headers = $exception->getHeaders();
        $retryAfter = $headers['Retry-After'] ?? 60;

        return $this->respond(
            $status,
            $code,
            $this->translate("errors.{$code}", ['seconds' => is_numeric($retryAfter) ? (string) $retryAfter : '60']),
            headers: $headers,
        );
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    private function serverError(int $status, Throwable $exception, array $headers = []): JsonResponse
    {
        // Developers see the real message; nobody else ever sees internals.
        $message = $this->app->environment('local', 'testing') && $exception->getMessage() !== ''
            ? $exception->getMessage()
            : $this->translate('errors.server_error');

        return $this->respond($status, 'server_error', $message, headers: $headers);
    }

    /**
     * @param  array<array-key, mixed>  $errors
     * @param  array<string, mixed>  $headers
     */
    private function respond(int $status, string $code, string $message, array $errors = [], array $headers = []): JsonResponse
    {
        $body = ['message' => $message, 'code' => $code];

        if ($status === Response::HTTP_UNPROCESSABLE_ENTITY && $errors !== []) {
            $body['errors'] = $errors;
        }

        return new JsonResponse($body, $status, $headers);
    }

    /**
     * @param  array<string, string>  $replace
     */
    private function translate(string $key, array $replace = []): string
    {
        $line = $this->translator->get($key, $replace);

        return is_string($line) ? $line : $key;
    }
}
