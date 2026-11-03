<?php

declare(strict_types=1);

use App\Exceptions\ApiExceptionRenderer;
use App\Exceptions\CurrencyInUseException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: env('API_PREFIX', 'api'),
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // There is no web login page: guests always get a 401, never a redirect.
        $middleware->redirectGuestsTo(static fn (): ?string => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $renderer = new ApiExceptionRenderer;

        // An expected business conflict, not a failure worth logging.
        $exceptions->dontReport(CurrencyInUseException::class);

        $exceptions->shouldRenderJsonWhen(ApiExceptionRenderer::shouldRenderJson(...));
        $exceptions->render(fn (Throwable $e, Request $request) => $renderer($e, $request));
    })->create();
