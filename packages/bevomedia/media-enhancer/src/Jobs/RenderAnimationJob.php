<?php

namespace BevoMedia\MediaEnhancer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use BevoMedia\MediaEnhancer\Models\MediaJob;

class RenderAnimationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $jobId,
        public string $text,
        public array $options = []
    ) {}

    public function handle()
    {
        $job = MediaJob::where('uuid', $this->jobId)->first();

        if (!$job) {
            return;
        }

        $job->update(['status' => 'processing']);

        try {
            $outputDir = config('media-ai.output_path', storage_path('app/output'));
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $output = rtrim($outputDir, '/') . "/{$this->jobId}.mp4";

            $command = [
                config('media-ai.python_path', 'python3'),
                config('media-ai.python_animate_script_path'),
                '--text', $this->text,
                '--output', $output,
                '--style', $this->options['style'] ?? 'fade',
                '--resolution', $this->options['resolution'] ?? '1920x1080',
                '--fps', $this->options['fps'] ?? 30,
            ];

            $result = Process::timeout(120)->run($command);

            if ($result->failed()) {
                throw new \Exception($result->errorOutput());
            }

            $job->update([
                'status' => 'completed',
                'output_path' => $output,
                'completed_at' => now(),
            ]);

            event(new \BevoMedia\MediaEnhancer\Events\MediaProcessingComplete($job));

        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }
}
