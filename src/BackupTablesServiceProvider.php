<?php

namespace WatheqAlshowaiter\BackupTables;

use Illuminate\Support\ServiceProvider;
use WatheqAlshowaiter\BackupTables\Commands\BackupTableCommand;

class BackupTablesServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                BackupTableCommand::class,
            ]);

            if($this->app->environment() === 'testing'){
                $this->loadMigrationsFrom(__DIR__.'/../tests/database/migrations');
            }
        }
    }
}
