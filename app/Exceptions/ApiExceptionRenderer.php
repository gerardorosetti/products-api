<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Renders every exception raised under the API prefix as the JSON error envelope:
 * {"message": string, "code": string, "errors"?: {field: [string]}}.
 */
final class ApiExceptionRenderer
{
    public static function shouldRenderJson(Request $request): bool
    {
        $prefix = trim((string) config('app.api_prefix'), '/');

        return $request->is($prefix, $prefix.'/*') || $request->expectsJson();
    }

    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! self::shouldRenderJson($request)) {
            return null;
        }

        return match (true) {
            $e instanceof AuthenticationException => $this->envelope(401, 'unauthenticated', 'Unauthenticated.'),
            $e instanceof AuthorizationException,
            $e instanceof AccessDeniedHttpException => $this->envelope(403, 'forbidden', 'This action is forbidden.'),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => $this->envelope(404, 'not_found', 'Resource not found.'),
            $e instanceof MethodNotAllowedHttpException => $this->envelope(
                405,
                'method_not_allowed',
                'Method not allowed.',
                headers: $e->getHeaders(),
            ),
            $e instanceof CurrencyInUseException => $this->envelope(409, 'currency_in_use', $e->getMessage()),
            $e instanceof ValidationException => $this->envelope(
                422,
                'validation_failed',
                $e->getMessage(),
                $e->errors(),
            ),
            $e instanceof TooManyRequestsHttpException => $this->envelope(
                429,
                'too_many_requests',
                'Too many requests.',
                headers: $e->getHeaders(),
            ),
            $e instanceof HttpExceptionInterface => $this->envelope(
                $e->getStatusCode(),
                'http_error',
                $e->getMessage() !== '' ? $e->getMessage() : 'HTTP error.',
                headers: $e->getHeaders(),
            ),
            config('app.debug') === true => null,
            default => $this->envelope(500, 'server_error', 'Server error.'),
        };
    }

    /**
     * @param  array<string, list<string>>|null  $errors
     * @param  array<string, string>  $headers
     */
    private function envelope(int $status, string $code, string $message, ?array $errors = null, array $headers = []): JsonResponse
    {
        $body = ['message' => $message, 'code' => $code];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return new JsonResponse($body, $status, $headers);
    }
}
