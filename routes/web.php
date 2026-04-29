<?php

use Illuminate\Support\Facades\Route;
use BevoMedia\MediaEnhancer\Services\AudioEnhancerService;
use App\Http\Controllers\MediaAIDemoController;

Route::get('/', function () {
    return view('welcome');
});

// Media AI Demo Routes
Route::get('/demo', [MediaAIDemoController::class, 'index'])->name('demo.index');
Route::get('/demo/upload', [MediaAIDemoController::class, 'handleUpload'])->name('demo.upload');
Route::post('/demo/upload', [MediaAIDemoController::class, 'handleUpload'])->name('demo.upload');
Route::get('/demo/status/{jobId}', [MediaAIDemoController::class, 'checkStatus'])->name('demo.status');
Route::get('/demo/download/{file}', [MediaAIDemoController::class, 'download'])->name('demo.download');

Route::get('/test-audio', function () {
    $service = app(AudioEnhancerService::class);
    return $service->process(storage_path('app/test1.mp3'));
});