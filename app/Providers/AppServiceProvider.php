<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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

        // The API documentation is public and read-only.
        Gate::define('viewApiDocs', static fn (): bool => true);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', static fn (Request $request): Limit => Limit::perMinute(60)
            ->by((string) ($request->user('sanctum')?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('login', static fn (Request $request): Limit => Limit::perMinute(5)
            ->by(Str::lower($request->string('email')->toString()).'|'.$request->ip()));
    }
}
