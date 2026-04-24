<?php

use Illuminate\Support\Facades\Route;
use BevoMedia\MediaEnhancer\Http\Controllers\MediaAIController;

Route::prefix('api/media-ai')->group(function () {
    Route::post('/enhance-audio', [MediaAIController::class, 'enhanceAudio']);
    
    // Client Demo Endpoints
    Route::get('/demo/audio', [\BevoMedia\MediaEnhancer\Http\Controllers\MediaAIDemoController::class, 'demoEnhanceAudio']);
    Route::get('/demo/caption', [\BevoMedia\MediaEnhancer\Http\Controllers\MediaAIDemoController::class, 'demoCaptionVideo']);
    Route::get('/demo/animate', [\BevoMedia\MediaEnhancer\Http\Controllers\MediaAIDemoController::class, 'demoAnimateText']);
});

