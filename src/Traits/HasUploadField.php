<?php

namespace Celxkodez\LaravelModelFileManager\Traits;

use Celxkodez\LaravelModelFileManager\HasUploadFieldObserver;

/**
 * Eloquent models that have uploaded file fields can use this trait.
 *
 * Checks for all fields that are UploadedFiles and saves them in a
 * public location.
 */
trait HasUploadField
{
    /**
     * Storage Driver
     * @note Must be "cloudinary" or only one specified Storage Disk Key on the filesystems.disks config
     * @var string
     */
    protected $driver = 'local';

    /**
     * Register observer to update the upload field before saving.
     *
     * @return void
     */
    public static function bootHasUploadField()
    {
        static::observe(new HasUploadFieldObserver);
    }
}
