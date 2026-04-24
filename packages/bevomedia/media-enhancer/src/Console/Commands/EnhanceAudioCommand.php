<?php

namespace BevoMedia\MediaEnhancer\Console\Commands;

use Illuminate\Console\Command;
use BevoMedia\MediaEnhancer\Services\AudioEnhancerService;

class EnhanceAudioCommand extends Command
{
    protected $signature = 'media:enhance {path : The path to the video file} 
                            {--preset=default : The enhancement preset (youtube, podcast, etc.)}
                            {--sync : Run the process synchronously}';

    protected $description = 'Extract and enhance audio from a video file';

    public function handle(AudioEnhancerService $service)
    {
        $path = $this->argument('path');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $this->info("Processing: {$path}");
        if ($this->option('preset') !== 'default') {
            $this->info("Using preset: " . $this->option('preset'));
        }

        try {
            $result = $service->process(
                $path, 
                $this->option('preset'), 
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
