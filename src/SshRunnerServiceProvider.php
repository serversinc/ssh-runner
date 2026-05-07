<?php

namespace Serversinc\SshRunner;

use Illuminate\Support\ServiceProvider;

class SshRunnerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('ssh-runner.php'),
            ], 'ssh-runner-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'ssh-runner-migrations');

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    #[\Override]
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'ssh-runner');

        // Register the main SshRunner class as a singleton
        $this->app->singleton('ssh-runner', fn ($app) => new SshRunner);
    }
}
