<?php

namespace Celxkodez\LaravelModelFileManager;

use Cloudinary\Cloudinary;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class LaravelModelFileManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $config = realpath(__DIR__.'/../resources/config/modelfilemanager.php');

        $this->publishes([
            $config => config_path('modelfilemanager.php')
        ]);
        //
        Storage::extend('cloudinary', function ($app, $config) {
            $adapter = new CloudinaryFileAdapter(new Cloudinary($config));

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
