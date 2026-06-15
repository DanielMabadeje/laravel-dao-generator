<?php

namespace DanielMabadeje\LaravelDaoGenerator;

use DanielMabadeje\LaravelDaoGenerator\Console\Commands\MakeDaoCommand;
use Illuminate\Support\ServiceProvider;

class DaoGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dao-generator.php', 'dao-generator');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeDaoCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/dao-generator.php' => config_path('dao-generator.php'),
            ], 'dao-generator-config');

            $this->publishes([
                __DIR__ . '/../resources/stubs/dao' => base_path('stubs/dao'),
            ], 'dao-generator-stubs');
        }
    }
}
