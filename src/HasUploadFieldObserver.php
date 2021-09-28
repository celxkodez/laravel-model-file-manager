<?php

namespace Celxkodez\LaravelModelFileManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class HasUploadFieldObserver {

    public function saving(Model $model)
    {
        $location = isset($model->uploadLocation) ? $model->uploadLocation : 'uploads';
        foreach ($model->getAttributes() as $key => $attribute) {
            if (is_object($attribute) && get_class($attribute) === UploadedFile::class) {
                $model->setAttribute($key, $attribute->storePublicly("$location"));
            }
        }
    }
}
