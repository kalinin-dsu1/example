<?php

namespace Bundles\Foundation\Uploads\Lib;

use Illuminate\Support\Facades\Facade;

class UploadsFacade extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'uploads';
    }

}
