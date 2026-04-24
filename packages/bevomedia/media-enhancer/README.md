# BevoMedia Media Enhancer

A powerful Laravel package for media processing, starting with AI-powered Audio Enhancement.

## Installation

### 1. Configure Repository (Local Development)
If you are using this package locally before publishing to Packagist, add this to your main Laravel `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "./packages/bevomedia/media-enhancer"
    }
],
```

### 2. Install via Composer
```bash
composer require bevomedia/media-enhancer
```

### 3. Publish Configuration
```bash
php artisan vendor:publish --tag=media-ai-config
```

### 4. Run Migrations
```bash
php artisan migrate
```

### 5. Install Python Dependencies
```bash
pip3 install deepfilternet faster-whisper moviepy torch requests
```

### 6. Environment Setup (`.env`)
Ensure these variables are set in your `.env` file:
```env
MEDIA_ENHANCER_FFMPEG_PATH=/usr/bin/ffmpeg
MEDIA_ENHANCER_PYTHON_PATH=/usr/bin/python3
MEDIA_ENHANCER_PYTHON_SCRIPT_PATH=/var/www/html/laravel-media-ai/packages/bevomedia/media-enhancer/python/enhance_audio.py
MEDIA_ENHANCER_PYTHON_TRANSCRIBE_PATH=/var/www/html/laravel-media-ai/packages/bevomedia/media-enhancer/python/transcribe.py
MEDIA_ENHANCER_PYTHON_ANIMATE_PATH=/var/www/html/laravel-media-ai/packages/bevomedia/media-enhancer/python/animate_text.py
MEDIA_ENHANCER_OUTPUT_PATH=/var/www/html/laravel-media-ai/storage/app/enhanced
MEDIA_ENHANCER_OPENAI_KEY=sk-your-openai-api-key-here
```

## Usage: Audio Enhancer (Module 1)

### Artisan Command
```bash
php artisan media:enhance {path_to_file} --preset=youtube
```
Available Presets: `default`, `youtube`, `podcast`, `tiktok`, `live`

### Service/Facade usage
```php
use BevoMedia\MediaEnhancer\Services\AudioEnhancerService;

$service = app(AudioEnhancerService::class);
$result = $service->process($inputPath, 'youtube');
```

## Testing the Module

### 1. Manual Testing via Artisan
```bash
php artisan media:enhance storage/app/test-audio.mp4 --preset=youtube --sync
```

### 2. Testing via Tinker (Synchronous)
```bash
php artisan tinker
>>> $result = app(\BevoMedia\MediaEnhancer\Services\AudioEnhancerService::class)->process(storage_path('app/test.mp4'), 'podcast', true);
>>> dd($result);
```

### 3. API Testing
**Endpoint:** `POST /api/media-ai/enhance-audio`  
**Payload (JSON):**
- `input_path`: String (absolute path)
- `preset`: String (optional, e.g., 'podcast')
- `sync`: Boolean (optional, default: false)

---

> [!NOTE]
> The `MEDIA_ENHANCER_PYTHON_SCRIPT_PATH` should point to the absolute path of the `enhance_audio.py` script within the package.

## Usage: Auto-Captioner (Module 2)

### Artisan Command
```bash
php artisan media:caption {path_to_video.mp4} --format=srt --burn-in
```
Available Options: 
- `--format`: `srt` or `vtt`
- `--model`: Defaults to `whisper-1` (OpenAI model name)
- `--burn-in`: Will bake the subtitles onto a copy of the video natively
- `--sync`: Runs synchronously without background Queue Job

### Service/Facade usage
```php
use BevoMedia\MediaEnhancer\Facades\VideoCaptioner;

$result = VideoCaptioner::generate(storage_path('app/video.mp4'), [
    'format'  => 'vtt',
    'burn_in' => true,
    'model'   => 'whisper-1'
]);
```

## Testing the Auto-Captioner Module

### 1. Manual Testing via Artisan (Asynchronous Queue Job)
Ensure your `.env` contains `MEDIA_ENHANCER_OPENAI_KEY` and run your worker:
```bash
php artisan queue:work
```
Dispatch the job:
```bash
php artisan media:caption storage/app/test-audio.mp4 --format=srt --burn-in
```

### 2. Manual Testing via Artisan (Synchronous)
```bash
php artisan media:caption storage/app/test-audio.mp4 --format=srt --burn-in --sync
```

## Usage: Text Animator (Module 3)

### Artisan Command
```bash
php artisan media:animate "Hello World" --style=typewriter
```
Available Options: 
- `text`: The text or phrase to animate (first argument).
- `--style`: The animation style (`fade`, `typewriter`, `slide`, `bounce`). Defaults to `fade`.
- `--resolution`: Video resolution, e.g. `1920x1080` or `1080x1920`. Defaults to `1920x1080`.
- `--fps`: Frames per second. Defaults to `30`.

### Service/Facade usage
```php
use BevoMedia\MediaEnhancer\Facades\TextAnimator;

$result = TextAnimator::render('Hello World', [
    'style'      => 'typewriter',
    'resolution' => '1080x1920',
    'fps'        => 30
]);
```

## Testing the Text Animator Module

### Manual Testing via Artisan (Asynchronous Queue Job)
Ensure your `.env` contains `MEDIA_ENHANCER_PYTHON_ANIMATE_PATH` and run your worker:
```bash
php artisan queue:work
```
Dispatch the jobs:
```bash
# Typewriter style
php artisan media:animate "Hello World" --style=typewriter

# Slide style with custom resolution
php artisan media:animate "Slide Animation" --style=slide --resolution=1080x1920 --fps=30

# Bounce style
php artisan media:animate "Bounce Animation" --style=bounce

# Fade style
php artisan media:animate "Fade Animation" --style=fade
```
