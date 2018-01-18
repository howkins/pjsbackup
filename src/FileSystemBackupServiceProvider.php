<?php

namespace howkins\pjsbackup;

use howkins\pjsbackup\Backup;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class FileSystemBackupServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('backup', function ($app) {
            return new Backup();
        });
    }
}