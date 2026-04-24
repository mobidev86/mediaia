<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Enhancer Settings
    |--------------------------------------------------------------------------
    |
    | Define your package settings here.
    |
    */

    'ffmpeg_path' => env('MEDIA_ENHANCER_FFMPEG_PATH', 'ffmpeg'),
    'python_path' => env('MEDIA_ENHANCER_PYTHON_PATH', 'python3'),
    'python_script_path' => env('MEDIA_ENHANCER_PYTHON_SCRIPT_PATH', __DIR__ . '/../python/enhance_audio.py'),
    'python_transcribe_script_path' => env('MEDIA_ENHANCER_PYTHON_TRANSCRIBE_PATH', __DIR__ . '/../python/transcribe.py'),
    'python_animate_script_path' => env('MEDIA_ENHANCER_PYTHON_ANIMATE_PATH', __DIR__ . '/../python/animate_text.py'),
    'output_path' => env('MEDIA_ENHANCER_OUTPUT_PATH', storage_path('app/output')),

    'caption' => [
        'openai_api_key' => env('MEDIA_ENHANCER_OPENAI_KEY', ''),
        'model' => env('WHISPER_MODEL', 'whisper-1'),
        'language' => env('WHISPER_LANG', ''),
        'burn_in' => true,
        'format' => 'srt',
    ],

    'animation' => [
        'default_font' => 'Arial',
        'default_size' => 72,
        'fps' => 30,
        'resolution' => '1920x1080',
        'style' => 'fade',
    ],
];

