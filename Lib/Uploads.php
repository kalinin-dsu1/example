<?php

namespace Bundles\Foundation\Uploads\Lib;

use Closure;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Bundles\Foundation\Uploads\Exceptions\MissedExtensionException;
use Bundles\Foundation\Uploads\Exceptions\NotAllowedExtensionException;
use Bundles\Foundation\Uploads\Exceptions\TooLargeException;
use Thumbor;

class Uploads
{

    /**
     * @var StorageContract
     */
    protected $storage;

    /**
     * @var null|Closure
     */
    protected $thumbsHandler = null;

    /**
     * Apply 'file' handler...
     */
    public function setFileStorage(): void
    {
        $this->storage = new FileStorage();
    }

    /**
     * Apply 'remote' handler...
     */
    public function setRemoteStorage(): void
    {
        $this->storage = new RemoteStorage();
    }

    /**
     * @param Closure $handler
     */
    public function setThumbsHandler(Closure $handler): void
    {
        $this->thumbsHandler = $handler;
    }

    /**
     * @param UploadedFile $file
     * @return array
     */
    public function put(UploadedFile $file): array
    {
        return $this->storage->put($file);
    }

    /**
     * @param string $type -- 'image' or 'file'...
     * @param UploadedFile $file
     * @return array
     */
    public function upload($type, UploadedFile $file): array
    {
        # Validate uploaded file:
        if ($type === 'file') {
            $this->checkFileType($file);
        } else { // ie `image`:
            $this->checkImageType($file);
        }

        $out = $this->put($file);
        $out['filename'] = $file->getClientOriginalName();
        $out['basename'] = $this->stripExtension($out['filename']);
        $out['extension'] = static::getUploadedFileExtension($file);
        return $out;
    }

    /**
     * @param string $hash_name
     * @param array $options
     * @option array 'thumbs' -- optional list of thumbnail sizes...
     * @return array
     */
    public function getImageInfo(string $hash_name, array $options = []): array
    {
        $out = $this->storage->hashNameToInfo($hash_name);
        if (array_key_exists('thumbs', $options)) {
            if (!$this->thumbsHandler) {
                throw new RuntimeException('Thumbnails handler is not defined!');
            }
            $handler = $this->thumbsHandler;
            $out['thumbs'] = $handler($options['thumbs'], $out, $this, $options);
        }
        return $out;
    }

    public function getImageWithWatermark(string $hash_name, array $options = [], $watermark = []): array
    {
        $out = $this->storage->hashNameToInfo($hash_name);
        if (array_key_exists('thumbs', $options)) {
            $this->thumbsHandler = function ($thumbs, $info, $watermark) {
                $out = [];
                $base_path = join('/', $info['base_arr']);
                foreach ($thumbs as $v) {
                    $size = array_map('intval', explode('x', $v));
                    if (count($size) !== 2) {
                        throw new RuntimeException("Invalid thumbnail size: '$v'!");
                    }
                    $out[$v] = (string)Thumbor::url($base_path)
                        ->resize($size[0], $size[1])
                        ->addFilter(
                            'watermark',
                            $watermark['file'],
                            $watermark['x'],
                            $watermark['y'],
                            $watermark['alpha']
                        );
                }
                return $out;
            };

            $handler = $this->thumbsHandler;
            $out['thumbs'] = $handler($options['thumbs'], $out, $watermark);
        }
        return $out;
    }

    /**
     * @param string $hash_name
     * @return array
     */
    public function getFileInfo(string $hash_name): array
    {
        return $this->storage->hashNameToInfo($hash_name);
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    public static function getUploadedFileExtension(UploadedFile $file): string
    {
        return strtolower($file->getClientOriginalExtension());
    }

    /**
     * @param UploadedFile $file
     * @throws NotAllowedExtensionException
     * @throws TooLargeException
     */
    protected function checkImageType(UploadedFile $file): void
    {
        # Check extension:
        $ext = static::getUploadedFileExtension($file);
        $allowed = config('uploads.allowed_images');
        if (!in_array($ext, $allowed)) {
            $allowedList = join(', ', $allowed);
            throw new NotAllowedExtensionException(
                "Недопустимый тип изображения: '$ext'!\nДопустимые типы:\n" . $allowedList
            );
        }

        # Check size:
        $max = config('uploads.image_max_bytes');
        $size = $file->getSize();
        if ($size > $max) {
            throw new TooLargeException("Максимальный размер изображения: $max байт. Получено $size байт.");
        }
    }

    /**
     * @param UploadedFile $file
     * @throws NotAllowedExtensionException
     * @throws TooLargeException
     */
    protected function checkFileType(UploadedFile $file): void
    {
        # Check extension:
        $ext = static::getUploadedFileExtension($file);
        $allowed = config('uploads.allowed_files');
        if (!in_array($ext, $allowed)) {
            $allowedList = join(', ', $allowed);
            throw new NotAllowedExtensionException(
                "Недопустимый тип файла: '$ext'!\nДопустимые типы:\n" . $allowedList
            );
        }

        # Check size:
        $max = config('uploads.file_max_bytes');
        $size = $file->getSize();
        if ($size > $max) {
            throw new TooLargeException("Максимальный размер файла: $max байт. Получено $size байт.");
        }
    }

    /**
     * Strips extension from given file name.
     * @param string $filename
     * @return string
     * @throws MissedExtensionException
     */
    protected function stripExtension(string $filename): string
    {
        $pos = strrpos($filename, '.');
        if ($pos >= 0) {
            return substr($filename, 0, $pos);
        }
        throw new MissedExtensionException();
    }

}
