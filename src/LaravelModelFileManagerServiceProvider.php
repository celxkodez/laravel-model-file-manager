<?php
/**
 * This file is part of the Laravel Model File Manager package.
 *
 * (c) Celestine Stephen Uko <decele2011@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $config = realpath(__DIR__ . '/../resources/config/modelfilemanager.php');

        $this->publishes([
            $config => config_path('modelfilemanager.php')
        ]);

        $cloudinary_config = config('filesystems.disks.cloudinary');

        if (! isset($cloudinary_config)) {
            config(['filesystems.disks.cloudinary' => [
                'driver' => 'cloudinary',
                'url' => config('modelfilemanager.cloudinary_url'),
            ]]);
        }

        //@TODO, this may be abstracted to depend on the user implementation
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
