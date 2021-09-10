<?php

namespace Pdik\laravelExactonline;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Pdik\Console\Commands\SyncExactOnline;

class ExactOnlineServiceProvider{
    public function boot(){
        $this->registerConfigs();
        $this->registerMigrations();
        $this->registerViews();
        $this->registerRoutes();
        $this->registerCommands();
    }
    private function registerConfigs()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('exact.php'),
            ], 'laravelExactonlineConfig');

        }
        $this->mergeConfigFrom(__DIR__ . '/../config/exact.php', 'laravelExactonline');

    }
    private function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravelExactonline');
    }

    private function registerRoutes()
    {
        $router = $this->app->make(Router::class);

        $router->group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }
    private function routeConfiguration(): array
    {
        return [
            'middleware' => ['web'],
            'as' => 'laravelExactonline::',
        ];
    }
    private function registerMigrations()
    {
        $this->publishes([
            __DIR__ . '/../database/migrations/2020_10_21_081923_create_exact_settings_table.php' => database_path('migrations/2020_10_21_081923_create_exact_settings_table.php'),
        ], 'laravelExactonline-migrations');
    }
    private function registerCommands(){
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncExactOnline::class
            ]);
        }
    }
}