<?php

namespace Mmanda\LaravelObfs\Providers;

use Illuminate\Support\ServiceProvider;
use Mmanda\LaravelObfs\Console\Commands\ObfuscateAllCommand;
use Mmanda\LaravelObfs\Console\Commands\ObfuscateDirectoryCommand;
use Mmanda\LaravelObfs\Console\Commands\ObfuscateFileCommand;
use Mmanda\LaravelObfs\Console\Commands\RestoreCommand;
use Mmanda\LaravelObfs\Services\ObfuscationService;

class ObfuscateServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mObfs.php', 'mObfs');

        $this->app->singleton(ObfuscationService::class, function ($app) {
            return new ObfuscationService();
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ObfuscateAllCommand::class,
                ObfuscateDirectoryCommand::class,
                ObfuscateFileCommand::class,
                RestoreCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/mObfs.php' => config_path('mObfs.php'),
                __DIR__.'/../config/yakpro-po.php.cnf' => config_path('mObfs.cnf'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../../mObfuscate_backups/' => base_path('M_obfuscate_backups'),
            ], 'backups');
        }
    }
}
