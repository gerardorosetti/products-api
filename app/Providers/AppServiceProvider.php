<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Support\Docs\ApiDocumentTransformer;
use App\Support\Docs\PatchOperationIdTransformer;
use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        DB::prohibitDestructiveCommands($this->app->isProduction());

        $this->configureRateLimiting();

        Scramble::configure()
            ->resolveOperationMethodsUsing(static function (Route $route): string|array {
                $methods = array_map('strtolower', $route->methods());

                // Document both verbs of PUT|PATCH routes instead of only the first one.
                return in_array('put', $methods, true) && in_array('patch', $methods, true) ? ['put', 'patch'] : $methods[0];
            })
            ->withOperationTransformers(PatchOperationIdTransformer::class)
            ->withDocumentTransformers(ApiDocumentTransformer::class);

        // The API documentation is public and read-only. The nullable user lets guests pass the gate.
        Gate::define('viewApiDocs', static fn (?User $user = null): bool => true);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute(60)
            ->by((string) ($request->user('sanctum')?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('login', static fn (Request $request): Limit => Limit::perMinute(5)
            ->by(Str::lower($request->string('email')->toString()).'|'.$request->ip()));
    }
}
