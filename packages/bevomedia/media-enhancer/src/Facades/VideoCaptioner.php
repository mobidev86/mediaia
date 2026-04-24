<?php

namespace BevoMedia\MediaEnhancer\Facades;

use Illuminate\Support\Facades\Facade;

class VideoCaptioner extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \BevoMedia\MediaEnhancer\Services\VideoCaptionerService::class;
    }
}
