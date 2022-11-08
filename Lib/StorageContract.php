<?php

namespace Bundles\Foundation\Uploads\Lib;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class StorageContract
{

    /**
     * @param UploadedFile $file
     * @return array
     */
    public function put(UploadedFile $file): array
    {
        // Sample output...
        return [
            'name' => 'File name...',
            'path' => 'File path...'
        ];
    }

    /**
     * @param string $hash_name
     * @return array
     */
    public function hashNameToInfo(string $hash_name): array
    {
        // Sample output...
        return [
            'name' => $hash_name,
            'link' => 'File link',
            'path' => 'File path',
            'extension' => 'jpg',
            'base_arr' => ['jpg', 'a', 'bc', 'abcdefg.jpg']
        ];
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    protected function getFileHash(UploadedFile $file): string
    {
        return md5_file($file->getRealPath());
    }

    /**
     * @param UploadedFile $file
     * @return int
     */
    protected function getSize(UploadedFile $file): int
    {
        return $file->getSize();
    }

    /**
     * For example: `e0e63030f94da6bfc46216dc3b7f7d6d.jpg` -> [
     *      'jpg',
     *      'e',
     *      '0e',
     *      'e0e63030f94da6bfc46216dc3b7f7d6d.jpg'
     * ]
     *
     * @param string $hash_name
     * @return array
     */
    protected function hashNameToPathArray(string $hash_name): array
    {
        return [
            substr($hash_name, strpos($hash_name, '.') + 1), // file extension...
            substr($hash_name, 0, 1),
            substr($hash_name, 1, 2),
            $hash_name
        ];
    }

}
