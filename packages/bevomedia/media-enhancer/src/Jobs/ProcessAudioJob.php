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

            $tmpDir = storage_path("app/tmp");
            if (!file_exists($tmpDir)) {
                mkdir($tmpDir, 0755, true);
            }

            $ffmpegPath = config('media-ai.ffmpeg_path', 'ffmpeg');
            $pythonPath = config('media-ai.python_path', 'python3');

            // Binary validations
            if (str_starts_with($ffmpegPath, '/') && !file_exists($ffmpegPath)) {
                throw new \Exception("FFmpeg executable not found at: {$ffmpegPath}");
            }
            if (str_starts_with($pythonPath, '/') && !file_exists($pythonPath)) {
                throw new \Exception("Python executable not found at: {$pythonPath}");
            }

            if (!file_exists($job->input_path)) {
                throw new \Exception("Input file missing before extraction: {$job->input_path}");
            }

            // 1. Extract audio with EQ filters
            $filters = $this->getFFmpegFilters($this->preset);
            
            $command = [
                $ffmpegPath,
                '-y',
                '-i', $job->input_path,
                '-vn',
                '-acodec', 'pcm_s16le',
                '-ar', '44100',
            ];

            if ($filters) {
                $command[] = '-af';
                $command[] = $filters;
            }

            $command[] = $this->input;

            $extractResult = Process::timeout(3600)->run($command);

            if ($extractResult->failed()) {
                throw new \Exception("FFmpeg audio extraction failed: " . $extractResult->errorOutput());
            }

            if (!file_exists($this->input)) {
                throw new \Exception("FFmpeg extraction reported success but output file is missing: {$this->input}");
            }

            $output = rtrim($outputDir, '/') . "/{$this->jobId}.wav";

            $result = Process::timeout(3600)->run([
                $pythonPath,
                config('media-ai.python_script_path'),
                '--input', $this->input,
                '--output', $output
            ]);

            if ($result->failed()) {
                throw new \Exception($result->errorOutput());
            }

            // Cleanup original and temp files on success
            if (file_exists($this->input)) {
                @unlink($this->input);
            }
            if ($job->input_path && file_exists($job->input_path)) {
                @unlink($job->input_path);
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
            // Only clean up temporary .wav if it still exists (original is handled in success block)
            if (file_exists($this->input)) {
                @unlink($this->input);
            }
        }
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