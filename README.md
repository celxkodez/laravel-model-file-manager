# laravel-model-file-manager

[![Latest Stable Version](http://poser.pugx.org/celxkodez/laravel-model-file-manager/v)](https://packagist.org/packages/celxkodez/laravel-model-file-manager) 
[![Total Downloads](http://poser.pugx.org/celxkodez/laravel-model-file-manager/downloads)](https://packagist.org/packages/celxkodez/laravel-model-file-manager) 
[![Latest Unstable Version](http://poser.pugx.org/celxkodez/laravel-model-file-manager/v/unstable)](https://packagist.org/packages/celxkodez/laravel-model-file-manager) 
[![License](http://poser.pugx.org/celxkodez/laravel-model-file-manager/license)](https://packagist.org/packages/celxkodez/laravel-model-file-manager) 
[![PHP Version Require](http://poser.pugx.org/celxkodez/laravel-model-file-manager/require/php)](https://packagist.org/packages/celxkodez/laravel-model-file-manager)

> A Laravel Package for handling file upload associated with model with ease

## Installation

[PHP](https://php.net) 7.3+ and [Composer](https://getcomposer.org) are required.

To get the latest version of Laravel model file manager, simply require it

```bash
composer require celxkodez/laravel-model-file-manager
```

Or add the following line to the require block of your `composer.json` file.

```
"celxkodez/laravel-model-file-manager": "1.0.*"
```

You'll then need to run `composer install` or `composer update` to download it and have the autoloader updated.

## Configuration

You can publish the configuration file using this command:

```bash
php artisan vendor:publish --provider="Celxkodez\LaravelModelFileManager\LaravelModelFileManagerServiceProvider"
```

A configuration-file named `modelfilemanager.php` with some sensible defaults will be placed in your `config` directory:

```php
<?php

return [

    /**
     * Cloudinary Url
     *
     */
    'cloudinary_url' => getenv('CLOUDINARY_URL', ''),
];
```

## Usage

### On your Model, add the ``HasUploadField`` trait.

```php

use Celxkodez\LaravelModelFileManager\Traits\HasUploadField;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasUploadField;
}
```

### On Controller or anywhere with uploaded file

```php
Post::create([
    ...
    'image' => request('file')
    ...
]);
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## The Big “Thank You”!

Please Star the repo and share across your network. that would be so nice!

And also don't forget to [follow me on twitter](https://twitter.com/mr_celx)!

Thanks Again!
Celestine Stephen.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
