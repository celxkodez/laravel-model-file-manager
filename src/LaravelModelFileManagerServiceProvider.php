<?php

namespace Celxkodez\LaravelModelFileManager;

use Cloudinary\Cloudinary;
use League\Flysystem\Filesystem;
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

        //todo, this may be abstracted to depend on the user implementation
        Storage::extend('cloudinary', function ($app, $config) {
            $adapter = new CloudinaryFileAdapter(new Cloudinary($config['url']));

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
