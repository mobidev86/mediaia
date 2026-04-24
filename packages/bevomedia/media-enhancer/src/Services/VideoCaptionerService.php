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
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $tmpInput = "{$tmpDir}/{$jobId}.wav";

        // Step 1: Extract audio using FFmpeg
        $command = [
            config('media-ai.ffmpeg_path', 'ffmpeg'),
            '-i', $inputPath,
            '-vn',
            '-acodec', 'pcm_s16le',
            '-ar', '16000', // Whisper works well with 16kHz
            '-ac', '1',
            $tmpInput
        ];

        $result = Process::run($command);

        if ($result->failed()) {
            throw new \Exception("FFmpeg audio extraction failed: " . $result->errorOutput());
        }

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
