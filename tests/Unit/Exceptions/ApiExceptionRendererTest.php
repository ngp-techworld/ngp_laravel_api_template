<?php

declare(strict_types=1);

use App\Exceptions\ApiException;
use App\Exceptions\ApiExceptionRenderer;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

function renderException(Throwable $exception): ?JsonResponse
{
    return app(ApiExceptionRenderer::class)->render($exception);
}

function domainException(int $status, string $code, array $errors = []): ApiException
{
    return new class($status, $code, $errors) extends ApiException
    {
        public function __construct(private int $httpStatus, private string $errorCodeValue, private array $fieldErrors)
        {
            parent::__construct('Domain message');
        }

        public function status(): int
        {
            return $this->httpStatus;
        }

        public function errorCode(): string
        {
            return $this->errorCodeValue;
        }

        public function errors(): array
        {
            return $this->fieldErrors;
        }
    };
}

beforeEach(function (): void {
    app()->setLocale('en');
});

it('renders validation errors with the field errors', function (): void {
    $response = renderException(ValidationException::withMessages(['email' => 'The email field is required.']));

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toBe([
            'message' => 'The given data was invalid.',
            'code' => 'validation_failed',
            'errors' => ['email' => ['The email field is required.']],
        ]);
});

it('renders a missing or invalid sign-in as unauthenticated', function (): void {
    $response = renderException(new AuthenticationException);

    expect($response->getStatusCode())->toBe(401)
        ->and($response->getData(true))->toBe([
            'message' => 'You need to sign in first.',
            'code' => 'unauthenticated',
        ]);
});

it('maps framework HTTP errors to stable codes', function (HttpException $exception, int $status, string $code): void {
    $response = renderException($exception);

    expect($response->getStatusCode())->toBe($status)
        ->and($response->getData(true)['code'])->toBe($code)
        ->and($response->getData(true))->not->toHaveKey('errors');
})->with([
    'policy denied' => [new AccessDeniedHttpException, 403, 'forbidden'],
    'model or route not found' => [new NotFoundHttpException, 404, 'not_found'],
    'wrong method' => [new MethodNotAllowedHttpException(['GET']), 405, 'method_not_allowed'],
    'expired CSRF token' => [new HttpException(419), 419, 'csrf_token_mismatch'],
    'maintenance mode' => [new HttpException(503), 503, 'service_unavailable'],
    'any other 4xx' => [new HttpException(413), 413, 'http_error'],
    'any other 5xx' => [new HttpException(502), 502, 'server_error'],
]);

it('tells the client when to retry after a rate limit', function (): void {
    $response = renderException(new ThrottleRequestsException(headers: ['Retry-After' => 42]));

    expect($response->getStatusCode())->toBe(429)
        ->and($response->headers->get('Retry-After'))->toBe('42')
        ->and($response->getData(true))->toBe([
            'message' => 'Too many requests. Try again in 42 seconds.',
            'code' => 'too_many_requests',
        ]);
});

it('renders a domain exception with its own status, code and message', function (): void {
    $response = renderException(domainException(409, 'slot_unavailable'));

    expect($response->getStatusCode())->toBe(409)
        ->and($response->getData(true))->toBe([
            'message' => 'Domain message',
            'code' => 'slot_unavailable',
        ]);
});

it('only adds field errors to 422 responses', function (): void {
    $conflict = renderException(domainException(409, 'slot_unavailable', ['slot' => ['Taken.']]));
    $invalid = renderException(domainException(422, 'invalid_credentials', ['email' => ['Wrong.']]));

    expect($conflict->getData(true))->not->toHaveKey('errors')
        ->and($invalid->getData(true)['errors'])->toBe(['email' => ['Wrong.']]);
});

it('shows developers the real message of an unexpected error', function (): void {
    $response = renderException(new RuntimeException('Connection refused'));

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getData(true))->toBe([
            'message' => 'Connection refused',
            'code' => 'server_error',
        ]);
});

it('hides the real message of an unexpected error outside local and testing', function (): void {
    app()->instance('env', 'production');

    $response = renderException(new RuntimeException('Connection refused'));

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getData(true))->toBe([
            'message' => 'Something went wrong. Please try again later.',
            'code' => 'server_error',
        ]);
});

it('leaves responses that were built on purpose alone', function (): void {
    $exception = new HttpResponseException(response()->json(['custom' => true], 418));

    expect(renderException($exception))->toBeNull();
});
