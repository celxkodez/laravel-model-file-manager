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
     * Register observer to update the upload field before saving.
     *
     * @return void
     */
    public static function bootHasUploadField()
    {
        static::observe(new HasUploadFieldObserver);
    }
}
