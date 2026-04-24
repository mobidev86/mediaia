<?php

namespace BevoMedia\MediaEnhancer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use BevoMedia\MediaEnhancer\Models\MediaJob;

class MediaProcessingComplete
{
    use Dispatchable, SerializesModels;

    public function __construct(public MediaJob $job)
    {
    }
}
