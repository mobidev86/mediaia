<?php

namespace BevoMedia\MediaEnhancer;

use Illuminate\Support\ServiceProvider;
use BevoMedia\MediaEnhancer\Services\AudioEnhancerService;

class MediaAIServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/media-ai.php', 'media-ai');

        $this->app->singleton(AudioEnhancerService::class, function () {
            return new AudioEnhancerService();
        });
        $this->app->singleton(\BevoMedia\MediaEnhancer\Services\VideoCaptionerService::class, function () {
            return new \BevoMedia\MediaEnhancer\Services\VideoCaptionerService();
        });
        $this->app->singleton(\BevoMedia\MediaEnhancer\Services\TextAnimatorService::class, function () {
            return new \BevoMedia\MediaEnhancer\Services\TextAnimatorService();
        });
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/media-ai.php' => config_path('media-ai.php'),
            ], 'media-ai-config');

            $this->commands([
                \BevoMedia\MediaEnhancer\Console\Commands\EnhanceAudioCommand::class,
                \BevoMedia\MediaEnhancer\Console\Commands\CaptionVideoCommand::class,
                \BevoMedia\MediaEnhancer\Console\Commands\AnimateTextCommand::class,
            ]);
        }
    }
}

