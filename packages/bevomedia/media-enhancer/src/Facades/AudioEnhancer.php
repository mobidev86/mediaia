<?php

namespace BevoMedia\MediaEnhancer\Facades;

use Illuminate\Support\Facades\Facade;

class AudioEnhancer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \BevoMedia\MediaEnhancer\Services\AudioEnhancerService::class;
    }
}
