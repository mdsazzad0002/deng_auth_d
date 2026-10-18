<?php

namespace Dengrweb\DAuth\Tests;

use Dengrweb\DAuth\DAuth;
use Dengrweb\DAuth\DAuthServiceProvider;
use Dengrweb\DAuth\Tests\Fixtures\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [DAuthServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('dauth.url', 'https://accounts.test');
        $app['config']->set('dauth.client_id', 'client-123');
        $app['config']->set('dauth.client_secret', 'secret-456');
        $app['config']->set('dauth.user_model', User::class);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Laravel's users table first, then the package's dauth_id column, in one migrate run.
        $this->loadMigrationsFrom([\Orchestra\Testbench\default_migration_path(), __DIR__.'/../database/migrations']);
    }

    protected function tearDown(): void
    {
        DAuth::forgetResolver();

        parent::tearDown();
    }
}
