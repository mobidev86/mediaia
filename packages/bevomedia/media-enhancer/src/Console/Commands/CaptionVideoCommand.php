<?php

namespace BevoMedia\MediaEnhancer\Console\Commands;

use Illuminate\Console\Command;
use BevoMedia\MediaEnhancer\Services\VideoCaptionerService;

class CaptionVideoCommand extends Command
{
    protected $signature = 'media:caption {path : The path to the video file}
                            {--model=whisper-1 : The model to use}
                            {--format=srt : The format of the captions (srt or vtt)}
                            {--burn-in : Whether to burn captions into the video}
                            {--sync : Run the process synchronously}';

    protected $description = 'Auto-caption a video using OpenAI Whisper';

    public function handle(VideoCaptionerService $service)
    {
        $path = $this->argument('path');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $options = [
            'model' => $this->option('model'),
            'format' => $this->option('format'),
            'burn_in' => $this->option('burn-in'),
        ];

        $this->info("Processing: {$path}");
        
        try {
            $result = $service->generate(
                $path, 
                $options, 
                $this->option('sync')
            );

            if ($this->option('sync')) {
                $this->info("Processing complete!");
                $this->info("Output saved to: " . $result['output_path']);
            } else {
                $this->info("Job dispatched! Job ID: " . $result['job_id']);
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
