<?php

namespace BevoMedia\MediaEnhancer\Facades;

use Illuminate\Support\Facades\Facade;

class TextAnimator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \BevoMedia\MediaEnhancer\Services\TextAnimatorService::class;
    }
}
