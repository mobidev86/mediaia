<?php

namespace BevoMedia\MediaEnhancer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use BevoMedia\MediaEnhancer\Models\MediaJob;

class ProcessCaptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public $jobId,
        public $audioInput,
        public $options = []
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

            $format = $this->options['format'] ?? 'srt';
            $subtitleOutput = rtrim($outputDir, '/') . "/{$this->jobId}.{$format}";

            // 1. Run transcribe Python script
            $apiKey = config('media-ai.caption.openai_api_key');
            $pythonPath = config('media-ai.python_path', 'python3');
            $scriptPath = config('media-ai.python_transcribe_script_path');

            if (!$apiKey) {
                throw new \Exception("OpenAI API Key is missing. Please set MEDIA_ENHANCER_OPENAI_KEY.");
            }

            $result = Process::env(['OPENAI_API_KEY' => $apiKey])->run([
                $pythonPath,
                $scriptPath,
                '--input', $this->audioInput,
                '--output', $subtitleOutput,
                '--format', $format,
                '--model', $this->options['model'] ?? 'whisper-1'
            ]);

            if ($result->failed()) {
                throw new \Exception("Transcription failed: " . $result->errorOutput());
            }

            // 2. Burning subtitles if required
            $finalOutput = $subtitleOutput;

            if (!empty($this->options['burn_in'])) {
                $videoOutput = rtrim($outputDir, '/') . "/{$this->jobId}_captioned.mp4";
                $originalVideo = $job->input_path;

                $burnResult = Process::run([
                    config('media-ai.ffmpeg_path', 'ffmpeg'),
                    '-i', $originalVideo,
                    '-vf', "subtitles={$subtitleOutput}",
                    '-c:a', 'copy', // Copy original audio
                    $videoOutput
                ]);

                if ($burnResult->failed()) {
                    throw new \Exception("Burn-in failed: " . $burnResult->errorOutput());
                }

                $finalOutput = $videoOutput;
            }

            $job->update([
                'status' => 'completed',
                'output_path' => $finalOutput,
                'completed_at' => now(),
            ]);

            \BevoMedia\MediaEnhancer\Events\MediaProcessingComplete::dispatch($job);

        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        } finally {
            if (file_exists($this->audioInput)) {
                unlink($this->audioInput);
            }
        }
    }
}
