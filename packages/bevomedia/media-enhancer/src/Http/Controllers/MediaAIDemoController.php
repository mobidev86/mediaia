<?php

namespace BevoMedia\MediaEnhancer\Http\Controllers;

use Illuminate\Routing\Controller;
use BevoMedia\MediaEnhancer\Facades\AudioEnhancer;
use BevoMedia\MediaEnhancer\Facades\VideoCaptioner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class MediaAIDemoController extends Controller
{
    public function demoEnhanceAudio()
    {
        // Must point to a real file in storage/app
        $inputPath = storage_path('app/audio-enhancer.mp3'); 

        if (!file_exists($inputPath)) {
            return response()->json([
                'success' => false,
                'message' => "Test file not found at: {$inputPath}. Please upload a file there to run the demo."
            ], 404);
        }

        try {
            // Run synchronously strictly for the demo endpoint
            $result = AudioEnhancer::process($inputPath, 'default', true);

            return response()->download($result['output_path']);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function demoCaptionVideo()
    {
        // Must point to a real file in storage/app
        $inputPath = storage_path('app/caption-video.mp4');

        if (!file_exists($inputPath)) {
            return response()->json([
                'success' => false,
                'message' => "Test video not found at: {$inputPath}. Please upload a file there to run the demo."
            ], 404);
        }

        try {
            // Run synchronously strictly for the demo endpoint
            $result = VideoCaptioner::generate($inputPath, [
                'format' => 'srt',
                'burn_in' => true, // Ensure captions are burned onto final video copy
            ], true);

            return response()->download($result['output_path']);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function demoAnimateText(\Illuminate\Http\Request $request)
    {
        $text = $request->input('text', 'Hello World');

        return response()->json([
            'status' => 'processing',
            'message' => 'Animation is being generated...'
        ]);
        
        $options = [
            'style'      => $request->input('style', 'fade'),
            'resolution' => $request->input('resolution', '1920x1080'),
            'fps'        => (int) $request->input('fps', 30),
        ];

        try {
            // Run synchronously strictly for the demo endpoint
            $result = \BevoMedia\MediaEnhancer\Facades\TextAnimator::render($text, $options, true);

            if (empty($result['output_path']) || !file_exists($result['output_path'])) {
                throw new \Exception("Video was not generated successfully.");
            }

            return response()->download($result['output_path']);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

