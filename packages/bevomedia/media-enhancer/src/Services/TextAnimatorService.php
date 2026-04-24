<?php

namespace BevoMedia\MediaEnhancer\Services;

use Illuminate\Support\Str;
use BevoMedia\MediaEnhancer\Jobs\RenderAnimationJob;
use BevoMedia\MediaEnhancer\Models\MediaJob;

class TextAnimatorService
{
    public function render(string $text, array $options = [], bool $sync = false): array
    {
        $jobId = (string) Str::uuid();

        // Merge with config defaults
        $config = config('media-ai.animation', []);
        $options = array_merge([
            'style'      => $config['style'] ?? 'fade',
            'resolution' => $config['resolution'] ?? '1920x1080',
            'fps'        => $config['fps'] ?? 30,
            'font'       => $config['default_font'] ?? 'Arial',
            'size'       => $config['default_size'] ?? 72,
        ], $options);

        // Create DB Record
        $job = MediaJob::create([
            'uuid'       => $jobId,
            'type'       => 'animation',
            'status'     => 'queued',
            'input_path' => null, // Text is the input
            'metadata'   => json_encode(array_merge(['text' => $text], $options))
        ]);

        // Handle Sync or Async
        if ($sync) {
            $jobInstance = new RenderAnimationJob($jobId, $text, $options);
            $jobInstance->handle();
            
            $job->refresh();
            return [
                'job_id'      => $jobId,
                'status'      => $job->status,
                'output_path' => $job->output_path
            ];
        }

        // Dispatch Job
        RenderAnimationJob::dispatch($jobId, $text, $options);

        return [
            'job_id' => $jobId,
            'status' => 'queued'
        ];
    }
}

