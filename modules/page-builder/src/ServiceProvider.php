<?php

namespace HansSchouten\LaravelPageBuilder;

use Illuminate\Support\Facades\Schema;
use HansSchouten\LaravelPageBuilder\Commands\CreateTheme;
use HansSchouten\LaravelPageBuilder\Commands\PublishDemo;
use HansSchouten\LaravelPageBuilder\Commands\PublishTheme;
use PHPageBuilder\PHPageBuilder;
use Exception;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Load our phpb_full_url override BEFORE PHPageBuilder helpers are loaded.
        // This must be in global namespace, so we use a separate file.
        require_once __DIR__ . '/helpers_override.php';

        $this->mergeConfigFrom(__DIR__ . '/../config/pagebuilder.php', 'pagebuilder');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     * @throws Exception
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'pagebuilder');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateTheme::class,
                PublishTheme::class,
                PublishDemo::class,
            ]);
        }

        // register singleton phpPageBuilder (lazy loaded)
        $this->app->singleton('phpPageBuilder', function($app) {
            $config = config('pagebuilder') ?? [];
            try {
                $config['storage']['database']['pdo'] = \Illuminate\Support\Facades\DB::connection()->getPdo();
            } catch (\Throwable $e) {
                // Fallback to default config if DB connection not established yet
            }
            return new LaravelPageBuilder($config);
        });

        $this->publishes([
            __DIR__ . '/../config/pagebuilder.php' => config_path('pagebuilder.php'),
        ], 'config');
        
        $this->publishes([
            __DIR__ . '/../themes/demo' => base_path((config('pagebuilder.theme.folder_url') ?? '/themes') . '/demo'),
        ], 'demo-theme');
    }
}
