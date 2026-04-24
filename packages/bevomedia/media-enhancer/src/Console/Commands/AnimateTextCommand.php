<?php

namespace BevoMedia\MediaEnhancer\Console\Commands;

use Illuminate\Console\Command;
use BevoMedia\MediaEnhancer\Services\TextAnimatorService;

class AnimateTextCommand extends Command
{
    protected $signature = 'media:animate {text : The text or phrase to animate}
                            {--style=fade : The animation style (fade, typewriter, slide, bounce)}
                            {--resolution=1920x1080 : The video resolution}
                            {--fps=30 : The frames per second}
                            {--sync : Run the process synchronously}';

    protected $description = 'Convert words or phrases into animated MP4 video clips';

    public function handle(TextAnimatorService $service)
    {
        $text = $this->argument('text');
        
        $options = [
            'style'      => $this->option('style'),
            'resolution' => $this->option('resolution'),
            'fps'        => (int) $this->option('fps'),
        ];

        $this->info("Requesting animation for: \"{$text}\"");
        $this->info("Style: {$options['style']} | Resolution: {$options['resolution']} | FPS: {$options['fps']}");

        try {
            $result = $service->render(
                $text, 
                $options, 
                $this->option('sync')
            );

            if ($this->option('sync')) {
                $this->info("Processing complete!");
                $this->info("Output saved to: " . $result['output_path']);
            } else {
                $this->info("Animation job dispatched! Job ID: " . $result['job_id']);
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
