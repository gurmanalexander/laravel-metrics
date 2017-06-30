<?php namespace GurmanAlexander\Metrics;

use GurmanAlexander\Metrics\Console\MetricsMakeCommand;
use Illuminate\Support\ServiceProvider;
use GurmanAlexander\Metrics\Console\MetricsTableCommand;

class MetricsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('metrics.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {

            $this->commands([
                MetricsTableCommand::class,
                MetricsMakeCommand::class,
            ]);
        }

        $this->mergeConfigFrom(
            __DIR__.'/config/config.php', 'metrics'
        );
    }
}
