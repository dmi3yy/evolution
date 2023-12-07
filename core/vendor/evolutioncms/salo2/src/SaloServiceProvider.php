<?php namespace EvolutionCMS\Salo;

use EvolutionCMS\Salo\Console\BuildCommand;
use EvolutionCMS\Salo\Console\DownCommand;
use EvolutionCMS\Salo\Console\InstallCommand;
use EvolutionCMS\Salo\Console\PublishCommand;
use EvolutionCMS\Salo\Console\UpCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SaloServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerCommands();
        $this->configurePublishing();
    }

    /**
     * Register the console commands for the package.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildCommand::class,
                DownCommand::class,
                InstallCommand::class,
                PublishCommand::class,
                UpCommand::class,
            ]);
        }
    }

    /**
     * Configure publishing for the package.
     *
     * @return void
     */
    protected function configurePublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../runtimes' => $this->app->basePath('docker'),
            ], ['salo', 'salo-docker']);

            $this->publishes([
                __DIR__ . '/../bin/salo' => $this->app->basePath('salo'),
            ], ['salo', 'salo-bin']);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            BuildCommand::class,
            DownCommand::class,
            InstallCommand::class,
            PublishCommand::class,
            UpCommand::class,
        ];
    }
}
