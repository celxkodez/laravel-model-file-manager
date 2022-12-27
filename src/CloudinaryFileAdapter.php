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

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\BadRequest;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Api\Exception\RateLimited;
use Cloudinary\Cloudinary;
use Celxkodez\LaravelModelFileManager\Events\FlysystemCloudinaryResponseLog;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;

class CloudinaryFileAdapter extends AbstractAdapter
{
    use NotSupportingVisibilityTrait;

    public $cloudinary;

    public function __construct( Cloudinary $cloudinary )
    {
        $this->cloudinary = $cloudinary;
    }

    /**
     * @inheritDoc
     * @return array | false
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents);
    }

    /**
     * @inheritDoc
     * @return  array | false
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource);
    }

    /**
     * @inheritDoc
     *
     * @return  array | false
     */
    public function update($path, $contents, Config $config)
    {
        return $this->upload($path, $contents);
    }

    /**
     * @inheritDoc
     *
     * @return  array | false
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->upload($path, $resource);
    }

    /**
     * Upload an object.
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#upload_method
     *
     * @param string|resource $body
     * @return  array | false
     */
    protected function upload(string $path, $body)
    {
        if (is_string($body)) {
            $tempFile = tmpfile();

            if (fwrite($tempFile, $body) === false) {
                return false;
            }
        }

        $path = trim($path, '/');

        $options = [
            'type' => 'upload',
            'public_id' => $path,
            'invalidate' => true,
            'use_filename' => true,
            'resource_type' => 'auto',
            'unique_filename' => false,
        ];

        if (config('flysystem-cloudinary.folder')) {
            $options['folder'] = config('flysystem-cloudinary.folder');
        }

        if (config('flysystem-cloudinary.upload_preset')) {
            $options['upload_preset'] = config('flysystem-cloudinary.upload_preset');
        }

        try {
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->upload($tempFile ?? $body, $options);
        } catch (ApiError $ex) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return $this->normalizeResponse($response, $path, $body);
    }

    /**
     * @inheritDoc
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#rename_method
     *
     * @return bool
     */
    public function rename($path, $newpath): bool
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $newpath = $this->ensureFolderIsPrefixed(trim($newpath, '/'));

        $options = [
            'invalidate' => true,
        ];

        try {
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->rename($path, $newpath, $options);
        } catch (\Exception $e ) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return true;
    }

    /**
     * @inheritDoc
     *
     * @return bool
     */
    public function copy($path, $newpath): bool
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $newpath = $this->ensureFolderIsPrefixed(trim($newpath, '/'));

        $metaRead = $this->readObject($path);

        if ($metaRead === false) {
            return false;
        }

        $metaUpload = $this->upload($newpath, $metaRead['contents']);

        if ($metaUpload === false) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     * @return bool
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#destroy_method
     */
    public function delete($path): bool
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        return $this->destroy($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function destroy(string $path): bool
    {
        $options = [
            'invalidate' => true,
        ];

        $options['resource_type'] = 'image';
        $response = $this
            ->cloudinary
            ->uploadApi()
            ->destroy($path, $options);
        event(new FlysystemCloudinaryResponseLog($response));

        if ($response->getArrayCopy()['result'] === 'ok') {
            return true;
        }

        $options['resource_type'] = 'raw';
        $response = $this
            ->cloudinary
            ->uploadApi()
            ->destroy($path, $options);

        event(new FlysystemCloudinaryResponseLog($response));

        if ($response->getArrayCopy()['result'] === 'ok') {
            return true;
        }

        $options['resource_type'] = 'video';
        $response = $this
            ->cloudinary
            ->uploadApi()
            ->destroy($path, $options);

        event(new FlysystemCloudinaryResponseLog($response));

        if ($response->getArrayCopy()['result'] === 'ok') {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     *
     * @return bool
     */
    public function deleteDir($dirname): bool
    {
        $dirname = $this->ensureFolderIsPrefixed(trim($dirname, '/'));

        $files = $this->listContents($dirname);

        foreach ($files as ['path' => $path]) {
            $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

            $this->destroy($path);
        }

        try {
            $response = $this
                ->cloudinary
                ->adminApi()
                ->deleteFolder($dirname);
        } catch (ApiError | RateLimited $ex) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return true;
    }

    /**
     * @inheritDoc
     *
     * @return  array | false
     */
    public function createDir($dirname, Config $config)
    {
        $dirname = $this->ensureFolderIsPrefixed(trim($dirname, '/'));

        try {
            $response = $this
                ->cloudinary
                ->adminApi()
                ->createFolder($dirname);
        } catch (\Throwable $exception) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        return [
            'path' => $dirname,
            'type' => 'dir',
        ];
    }

    /**
     * @inheritDoc
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#explicit_method
     *
     * @return  array | bool | null
     */
    public function has($path)
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        try {
            $this->explicit($path);
        } catch (NotFound $ex) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     *
     * @return array | false
     */
    public function read($path)
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $meta = $this->readObject($path);

        if ($meta === false) {
            return false;
        }

        return $meta;
    }

    /**
     * @inheritDoc
     *
     * @return array | false
     */
    public function readStream($path)
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        $meta = $this->readObject($path);

        if ($meta === false) {
            return false;
        }

        $tempFile = tmpfile();

        if (fwrite($tempFile, $meta['contents']) === false) {
            return false;
        }

        if (rewind($tempFile) === false) {
            return false;
        }

        unset($meta['contents']);

        $meta['stream'] = $tempFile;

        return $meta;
    }

    /**
     * Read an object.
     *
     * https://cloudinary.com/documentation/image_upload_api_reference#explicit_method
     *
     * @return array | bool
     */
    protected function readObject(string $path)
    {
        try {
            $response = $this->explicit($path);
        } catch (NotFound $ex) {
            return false;
        }

        ['secure_url' => $url] = $response->getArrayCopy();

        try {
            $contents = Http::get($url)->throw()->body();
        } catch (RequestException $ex1) {
            return false;
        }

        return $this->normalizeResponse($response, $path, $contents);
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false): array
    {
        $directory = $this->ensureFolderIsPrefixed(trim($directory, '/'));

        $options = [
            'type' => 'upload',
            'prefix' => $directory,
            'max_results' => 500,
        ];

        try {
            $options['resource_type'] = 'raw';
            $responseRawFiles = $this
                ->cloudinary
                ->adminApi()
                ->assets($options);

            $options['resource_type'] = 'image';
            $responseImageFiles = $this
                ->cloudinary
                ->adminApi()
                ->assets($options);

            $options['resource_type'] = 'video';
            $responseVideoFiles = $this
                ->cloudinary
                ->adminApi()
                ->assets($options);

            $responseDirectories = $this
                ->cloudinary
                ->adminApi()
                ->subFolders($directory);
        } catch (\Throwable $exception) {
            return [];
        }

        event(new FlysystemCloudinaryResponseLog($responseRawFiles));
        event(new FlysystemCloudinaryResponseLog($responseImageFiles));
        event(new FlysystemCloudinaryResponseLog($responseVideoFiles));
        event(new FlysystemCloudinaryResponseLog($responseDirectories));

        $rawFiles = array_map(function (array $resource) {
            return $this->normalizeResponse($resource, $resource['public_id']);
        }, $responseRawFiles->getArrayCopy()['resources']);

        $imageFiles = array_map(function (array $resource) {
            return $this->normalizeResponse($resource, $resource['public_id']);
        }, $responseImageFiles->getArrayCopy()['resources']);

        $videoFiles = array_map(function (array $resource) {
            return $this->normalizeResponse($resource, $resource['public_id']);
        }, $responseVideoFiles->getArrayCopy()['resources']);

        $folders = array_map(function (array $resource) {
            $path = $this->ensurePrefixedFolderIsRemoved($resource['path']);

            return [
                'type' => 'dir',
                'path' => $path,
                'name' => $resource['name'],
            ];
        }, $responseDirectories->getArrayCopy()['folders']);

        return array_merge([], $rawFiles, $imageFiles, $videoFiles, $folders);
    }

    /**
     * @inheritDoc
     *
     * @return array | false
     */
    public function getMetadata($path)
    {
        $meta = $this->readObject($path);

        if ($meta === false) {
            return false;
        }

        return $meta;
    }

    /**
     * @inheritDoc
     *
     * @return array | false
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @inheritDoc
     *
     * @return array | false
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @inheritDoc
     *
     * @return array | false
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * @param string $path
     */
    public function getUrl(string $path)
    {
        $path = $this->ensureFolderIsPrefixed(trim($path, '/'));

        try {
            $response = $this->explicit($path);
        } catch (\Throwable $exception) {
            return false;
        }

        event(new FlysystemCloudinaryResponseLog($response));

        [
            'url' => $url,
            'secure_url' => $secure_url,
        ] = $response->getArrayCopy();

        if (config('flysystem-cloudinary.secure_url')) {
            return $secure_url;
        }

        return $url;
    }

    /**
     * @throws \Exception
     */
    protected function explicit(string $path)
    {
        $options = [
            'type' => 'upload',
        ];

        try {
            $options['resource_type'] = 'image';
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->explicit($path, $options);

            event(new FlysystemCloudinaryResponseLog($response));

            return $response;
        } catch (\Throwable $exception) {
        }

        try {
            $options['resource_type'] = 'raw';
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->explicit($path, $options);

            event(new FlysystemCloudinaryResponseLog($response));

            return $response;
        } catch (\Throwable $exception) {
        }

        try {
            $options['resource_type'] = 'video';
            $response = $this
                ->cloudinary
                ->uploadApi()
                ->explicit($path, $options);

            event(new FlysystemCloudinaryResponseLog($response));

            return $response;
        } catch (\Throwable $e) {
        }
    }

    protected function ensureFolderIsPrefixed(string $path): string
    {
        if (config('flysystem-cloudinary.folder')) {
            $folder = trim(config('flysystem-cloudinary.folder'), '/');

            return "{$folder}/$path";
        }

        return $path;
    }

    protected function ensurePrefixedFolderIsRemoved(string $path): string
    {
        if (config('flysystem-cloudinary.folder')) {
            $prefix = config('flysystem-cloudinary.folder') . '/';

            return Str::of($path)
                ->after($prefix)
                ->__toString();
        }

        return $path;
    }

    /**
     * Normalize the object result array.
     *
     * https://flysystem.thephpleague.com/v1/docs/architecture/
     *
     * @param string|resource|null $body
     */
    protected function normalizeResponse(
        $response,
        string $path,
        $body = null
    ): array {
        $path = $this->ensurePrefixedFolderIsRemoved($path);

        return [
            'contents' => $body,
            'etag' => Arr::get($response, 'etag'),
            'mimetype' => Util::guessMimeType($path, $body) ?? 'text/plain',
            'path' => $path,
            'size' => Arr::get($response, 'bytes'),
            'timestamp' => strtotime(Arr::get($response, 'created_at')),
            'type' => 'file',
            'version' => Arr::get($response, 'version'),
            'versionid' => Arr::get($response, 'version_id'),
            'visibility' => Arr::get($response, 'access_mode') === 'public' ? 'public' : 'private',
        ];
    }
}
