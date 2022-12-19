<?php

namespace Celxkodez\LaravelModelFileManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class HasUploadFieldObserver {

    /**
     * @throws \Exception
     */
    public function saving(Model $model)
    {
        $configured_drivers = config('filesystems.disks');
        $driver = $model->driver ?? config('filesystems.default');
        $configured_drivers = array_merge(['cloudinary'], $configured_drivers);

        dd($configured_drivers);
        if (! in_array($driver,$configured_drivers )) {
            $disks = implode(", ", $configured_drivers);

            throw new \Exception("Invalid Storage Specified \"{$driver}\", Accepted Disks \"$disks\"");
        }

        $location = $model->uploadLocation ?? 'uploads';
        foreach ($model->getAttributes() as $key => $attribute) {
            if (is_object($attribute) && get_class($attribute) === UploadedFile::class) {
                $model->setAttribute($key, $attribute->storePublicly("$location"));
            }
        }
    }
}
