<?php

namespace Bundles\Foundation\Uploads\Lib;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Bundles\Foundation\Uploads\Exceptions\RemoteUploadException;

use function GuzzleHttp\Psr7\stream_for;

class RemoteStorage extends StorageContract
{

    /**
     * @param UploadedFile $file
     * @return array
     * @throws RemoteUploadException
     */
    public function put(UploadedFile $file): array
    {
        $ext = Uploads::getUploadedFileExtension($file);
        $hash = $this->getFileHash($file);
        $size = $this->getSize($file);
        $client = new Client();
        $fileStream = stream_for(file_get_contents($file->getRealPath()));
        $uploadUrl = config('uploads.remote_storage.server') . config('uploads.remote_storage.upload_location');
        $response = $client->post($uploadUrl, [
            'http_errors' => false,
            'headers' => [
                'content-type' => 'application/octet-stream',
                'Secret-Key' => config('uploads.remote_storage.secret_key'),
                'File-Ext' => $ext,
                'File-Hash' => $hash,
                'File-Size' => $size
            ],
            'body' => $fileStream
        ]);
        $res = json_decode((string)$response->getBody(), true);
        if (isset($res['success']) && $res['success']) {
            return [
                'name' => $res['filename'],
                'path' => config('uploads.remote_storage.server')
                    . config('uploads.remote_storage.storage_location')
                    . "/{$res['ext']}/{$res['filename'][0]}/{$res['filename'][1]}{$res['filename'][2]}/{$res['filename']}"
            ];
        }
        throw new RemoteUploadException($res['error'] ?? 'Unknown error!');
    }

    /**
     * @param string $hash_name
     * @return array
     */
    public function hashNameToInfo(string $hash_name): array
    {
        $base_path = $this->hashNameToPathArray($hash_name);
        $link = config('uploads.remote_storage.server')
            . config('uploads.remote_storage.storage_location')
            . '/' . join('/', $base_path);
        return [
            'name' => $hash_name,
            'link' => $link,
            'path' => $link,
            'extension' => $base_path[0], // Be careful with extension position!
            'base_arr' => $base_path
        ];
    }

}
