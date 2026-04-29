<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use BevoMedia\MediaEnhancer\Facades\AudioEnhancer;
use BevoMedia\MediaEnhancer\Facades\VideoCaptioner;
use BevoMedia\MediaEnhancer\Facades\TextAnimator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use BevoMedia\MediaEnhancer\Models\MediaJob;

class MediaAIDemoController extends Controller
{
    /**
     * Display the demo page.
     */
    public function index()
    {
        return view('media-ai-demo');
    }

    /**
     * Handle media processing requests.
     */
    public function handleUpload(Request $request)
    {
        $type = $request->input('type');

        try {
            switch ($type) {
                case 'enhance_audio':
                    return $this->enhanceAudio($request);
                case 'caption_video':
                    return $this->captionVideo($request);
                case 'animate_text':
                    return $this->animateText($request);
                default:
                    return response()->json(['error' => 'Invalid processing type.'], 400);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function enhanceAudio(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:mp3,wav,m4a,mp4,mov',
        ]);

        $path = $request->file('file')->store('temp');
        $inputPath = Storage::path($path);

        $result = AudioEnhancer::process($inputPath, 'default', false);

        return response()->json([
            'success' => true,
            'job_id' => $result['job_id'],
            'status' => $result['status']
        ]);
    }

    protected function captionVideo(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:mp4,mov,avi,mkv',
        ]);

        $path = $request->file('file')->store('temp');
        $inputPath = Storage::path($path);

        $result = VideoCaptioner::generate($inputPath, [
            'format' => 'srt',
            'burn_in' => true,
        ], false);

        return response()->json([
            'success' => true,
            'job_id' => $result['job_id'],
            'status' => $result['status']
        ]);
    }

    protected function animateText(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:300',
            'style' => 'nullable|string',
        ]);

        set_time_limit(120);

        $text = $request->input('text');
        $options = [
            'style' => $request->input('style', 'fade'),
            'resolution' => '1280x720',
            'fps' => 24,
        ];

        $result = TextAnimator::render($text, $options, false);

        return response()->json([
            'success' => true,
            'job_id' => $result['job_id'],
            'status' => $result['status']
        ]);
    }

    /**
     * Check the status of a media job.
     */
    public function checkStatus($jobId)
    {
        $job = MediaJob::where('uuid', $jobId)->first();

        if (!$job) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        $response = [
            'status' => $job->status,
        ];

        if ($job->status === 'completed') {
            $response['download_url'] = route('demo.download', ['file' => basename($job->output_path)]);
            $response['filename'] = basename($job->output_path);
        } elseif ($job->status === 'failed') {
            $response['error'] = $job->error_message ?? 'Unknown processing error.';
        }

        return response()->json($response);
    }

    /**
     * Download processed media.
     * Note: In a real app, you'd want better security/validation for this.
     */
    public function download($file)
    {
        // Try common output locations used by the package
        $paths = [
            storage_path("app/public/enhanced/{$file}"),
            storage_path("app/public/captioned/{$file}"),
            storage_path("app/public/animations/{$file}"),
            storage_path("app/enhanced/{$file}"),
            storage_path("app/captioned/{$file}"),
            storage_path("app/animations/{$file}"),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return response()->download($path);
            }
        }

        return abort(404, "File not found: " . $file);
    }
}
