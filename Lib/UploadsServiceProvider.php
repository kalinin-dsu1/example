<?php

namespace Bundles\Foundation\Uploads\Lib;

use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Thumbor;

class UploadsServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind('uploads', function () {
            $uploads = new Uploads();
            if (config('uploads.storage_type') === 'remote') {
                $uploads->setRemoteStorage();
            } else {
                $uploads->setFileStorage();
            }
            $uploads->setThumbsHandler(function ($thumbs, $info) {
                $out = [];
                $base_path = join('/', $info['base_arr']);
                foreach ($thumbs as $v) {
                    $size = array_map('intval', explode('x', $v));
                    if (count($size) !== 2) {
                        throw new RuntimeException("Invalid thumbnail size: '$v'!");
                    }
                    $out[$v] = (string)Thumbor::url($base_path)->resize($size[0], $size[1]);
                }
                return $out;
            });
            return $uploads;
        });
    }

}
