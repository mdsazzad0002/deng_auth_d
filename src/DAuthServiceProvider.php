<?php

namespace Dengrweb\DAuth;

use Illuminate\Support\ServiceProvider;

class DAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dauth.php', 'dauth');

        $this->app->bind(DAuthClient::class, fn ($app) => new DAuthClient(
            url: rtrim((string) $app['config']->get('dauth.url'), '/'),
            clientId: $app['config']->get('dauth.client_id'),
            clientSecret: $app['config']->get('dauth.client_secret'),
            redirect: $app['config']->get('dauth.redirect') ?: url(trim($app['config']->get('dauth.route_prefix', 'auth/dauth'), '/').'/callback'),
            scopes: $app['config']->get('dauth.scopes', ['profile', 'email']),
        ));
    }

    public function boot(): void
    {
        if (config('dauth.routes')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/dauth.php' => config_path('dauth.php')], 'dauth-config');
            $this->publishesMigrations([__DIR__.'/../database/migrations' => database_path('migrations')], 'dauth-migrations');
        }

        if (config('dauth.share_inertia') && class_exists(\Inertia\Inertia::class)) {
            \Inertia\Inertia::share('dauth', fn () => DAuth::sharedProps());
        }
    }
}
