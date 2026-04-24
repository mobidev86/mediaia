<?php

namespace BevoMedia\MediaEnhancer\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Process;
use BevoMedia\MediaEnhancer\Jobs\ProcessAudioJob;
use BevoMedia\MediaEnhancer\Models\MediaJob;

class AudioEnhancerService
{
    public function process(string $inputPath, string $preset = 'default', bool $sync = false): array
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

        // Step 1: Extract audio with EQ filters
        $filters = $this->getFFmpegFilters($preset);
        
        $command = [
            config('media-ai.ffmpeg_path', 'ffmpeg'),
            '-i', $inputPath,
            '-vn',
            '-acodec', 'pcm_s16le',
            '-ar', '44100',
        ];

        if ($filters) {
            $command[] = '-af';
            $command[] = $filters;
        }

        $command[] = $tmpInput;

        $result = Process::run($command);

        if ($result->failed()) {
            throw new \Exception("FFmpeg audio extraction failed: " . $result->errorOutput());
        }

        // Step 2: Save DB
        $job = MediaJob::create([
            'uuid' => $jobId,
            'type' => 'audio',
            'status' => 'queued',
            'input_path' => $inputPath,
            'metadata' => json_encode(['preset' => $preset])
        ]);

        // Step 3: Handle Sync or Async
        if ($sync) {
            $jobInstance = new ProcessAudioJob($jobId, $tmpInput, $preset);
            $jobInstance->handle();
            
            $job->refresh();
            return [
                'job_id' => $jobId,
                'status' => $job->status,
                'output_path' => $job->output_path
            ];
        }

        ProcessAudioJob::dispatch($jobId, $tmpInput, $preset);

        return [
            'job_id' => $jobId,
            'status' => 'queued'
        ];
    }

    private function getFFmpegFilters(string $preset): ?string
    {
        return match (strtolower($preset)) {
            'podcast' => 'bass=g=3,treble=g=2,loudnorm',
            'youtube' => 'treble=g=3,loudnorm',
            'tiktok'  => 'equalizer=f=1000:t=q:w=1:g=2,loudnorm',
            'live'    => 'compand=0.3|0.3:6:-90/-60/-60/-40/-40/-20/-20/0/-20:6:0:-90:0.2,loudnorm',
            default   => 'loudnorm',
        };
    }
}