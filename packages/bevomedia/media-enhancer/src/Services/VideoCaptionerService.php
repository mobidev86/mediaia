<?php

namespace BevoMedia\MediaEnhancer\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Process;
use BevoMedia\MediaEnhancer\Jobs\ProcessCaptionJob;
use BevoMedia\MediaEnhancer\Models\MediaJob;

class VideoCaptionerService
{
    public function generate(string $inputPath, array $options = [], bool $sync = false): array
    {
        if (!file_exists($inputPath)) {
            throw new \Exception("Input file not found: {$inputPath}");
        }

        $jobId = (string) Str::uuid();

        $tmpDir = storage_path("app/tmp");

        $tmpInput = "{$tmpDir}/{$jobId}.wav";

        // Note: Audio extraction has been moved to ProcessCaptionJob for asynchronous execution

        // Step 2: Save DB
        $job = MediaJob::create([
            'uuid' => $jobId,
            'type' => 'caption',
            'status' => 'queued',
            'input_path' => $inputPath,
            'metadata' => json_encode($options)
        ]);

        // Step 3: Handle Sync or Async
        if ($sync) {
            $jobInstance = new ProcessCaptionJob($jobId, $tmpInput, $options);
            $jobInstance->handle();
            
            $job->refresh();
            return [
                'job_id' => $jobId,
                'status' => $job->status,
                'output_path' => $job->output_path
            ];
        }

        ProcessCaptionJob::dispatch($jobId, $tmpInput, $options);

        return [
            'job_id' => $jobId,
            'status' => 'queued'
        ];
    }
}
