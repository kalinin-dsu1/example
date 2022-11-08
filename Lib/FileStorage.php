<?php

namespace Bundles\Foundation\Uploads\Lib;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileStorage extends StorageContract
{

    /**
     * @param UploadedFile $file
     * @return array
     */
    public function put(UploadedFile $file): array
    {
        $ext = Uploads::getUploadedFileExtension($file);
        $dir = join(DIRECTORY_SEPARATOR, [config('uploads.file_storage.upload_dir'), $ext]);

        # Add subdirectories:
        $hash = $this->getFileHash($file);
        $dir = join(DIRECTORY_SEPARATOR, [$dir, $hash[0], $hash[1] . $hash[2]]);

        # Move file:
        $fileName = "$hash.$ext";
        $file->move($dir, $fileName);

        return [
            'name' => $fileName,
            'path' => join(DIRECTORY_SEPARATOR, [$dir, $fileName])
        ];
    }

    /**
     * @param string $hash_name
     * @return array
     */
    public function hashNameToInfo(string $hash_name): array
    {
        $base_path = $this->hashNameToPathArray($hash_name);
        $full_path = $base_path;
        array_unshift($full_path, config('uploads.file_storage.upload_dir'));
        return [
            'name' => $hash_name,
            'link' => config('uploads.file_storage.storage_location') . '/' . join('/', $base_path),
            'path' => join(DIRECTORY_SEPARATOR, $full_path),
            'extension' => $base_path[0], // Be careful with extension position!
            'base_arr' => $base_path
        ];
    }

}
