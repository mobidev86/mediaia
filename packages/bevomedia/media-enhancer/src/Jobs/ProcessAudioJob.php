<?php 
namespace BevoMedia\MediaEnhancer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use BevoMedia\MediaEnhancer\Models\MediaJob;

class ProcessAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels; // ✅ MUST HAVE

    public function __construct(
        public $jobId,
        public $input,
        public $preset = 'default'
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

            $output = rtrim($outputDir, '/') . "/{$this->jobId}.wav";

            $result = Process::run([
                config('media-ai.python_path', 'python3'),
                config('media-ai.python_script_path'),
                '--input', $this->input,
                '--output', $output
            ]);

            if ($result->failed()) {
                throw new \Exception($result->errorOutput());
            }

            $job->update([
                'status' => 'completed',
                'output_path' => $output,
                'completed_at' => now(),
                'metadata' => json_encode([
                    'model' => 'deepfilternet',
                    'preset' => $this->preset
                ])
            ]);

            \BevoMedia\MediaEnhancer\Events\MediaProcessingComplete::dispatch($job);

        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        } finally {
            if (file_exists($this->input)) {
                unlink($this->input);
            }

            if ($job && $job->input_path && file_exists($job->input_path)) {
                @unlink($job->input_path);
            }
        }
    }
}