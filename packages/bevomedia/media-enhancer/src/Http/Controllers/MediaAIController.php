<?php

namespace BevoMedia\MediaEnhancer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use BevoMedia\MediaEnhancer\Services\AudioEnhancerService;

class MediaAIController extends Controller
{
    public function enhanceAudio(Request $request, AudioEnhancerService $service)
    {
        $request->validate([
            'file' => 'required_without:input_path|file|mimes:mp4,mov,mp3,wav,m4a',
            'input_path' => 'required_without:file|string',
            'preset' => 'nullable|string',
            'sync' => 'nullable|boolean'
        ]);

        try {
            if ($request->hasFile('file')) {
                $path = $request->file('file')->store('uploads');
                $inputPath = storage_path("app/{$path}");
            } else {
                $inputPath = $request->input('input_path');
            }

            $result = $service->process(
                $inputPath, 
                $request->input('preset', 'default'), 
                $request->boolean('sync')
            );
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
