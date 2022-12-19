<?php

namespace Celxkodez\LaravelModelFileManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class HasUploadFieldObserver {

    /**
     * @throws \Exception
     */
    public function saving(Model $model)
    {
        $configured_drivers = config('filesystems.disks');
        $driver = $model->driver ?? config('filesystems.default');

        if (! in_array($driver,array_keys($configured_drivers) )) {
            $disks = implode(", ", array_keys($configured_drivers));

            throw new \Exception("Invalid Storage Specified \"{$driver}\", Accepted Disks \"$disks\"");
        }

        $location = $model->uploadLocation ?? 'uploads';
        foreach ($model->getAttributes() as $key => $attribute) {
            if (is_object($attribute) && get_class($attribute) === UploadedFile::class) {
                $model->setAttribute($key, Storage::putFile("$location", $attribute));
            }
        }
    }
}
