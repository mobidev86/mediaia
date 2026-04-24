<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media AI Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
        }
        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .gradient-text {
            background: linear-gradient(135deg, #60a5fa 0%, #a855f7 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .loader {
            border-top-color: #3b82f6;
            animation: spinner 1.5s linear infinite;
        }
        @keyframes spinner {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="min-h-screen pb-20">
    <div class="container mx-auto px-4 pt-12">
        <!-- Header -->
        <header class="text-center mb-16">
            <h1 class="text-5xl font-extrabold mb-4 gradient-text">Media AI Toolkit</h1>
            {{-- <p class="text-slate-400 text-lg max-w-2xl mx-auto">
                Experience the power of AI-driven media enhancement. Clean audio, generate captions, and animate text with a single click.
            </p> --}}
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Audio Enhancer Card -->
            <div class="glass rounded-2xl p-8 flex flex-col h-full transform transition-all hover:scale-[1.02]">
                <div class="bg-blue-500/10 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
                </div>
                <h3 class="text-2xl font-bold mb-3">Audio Enhancer</h3>
                <p class="text-slate-400 mb-8 flex-grow">Remove noise, balance levels, and make your audio sound professional instantly.</p>
                
                <form onsubmit="handleProcess(event, 'enhance_audio')" class="space-y-4">
                    <div class="relative group">
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-700 rounded-xl cursor-pointer hover:border-blue-500 hover:bg-blue-500/5 transition-all">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <span class="text-sm text-slate-400 group-hover:text-blue-400">Upload audio file</span>
                            </div>
                            <input type="file" name="file" class="hidden" required accept="audio/*,video/*" />
                        </label>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3 rounded-xl transition-colors">Process Audio</button>
                </form>
                <div id="status-enhance_audio" class="mt-4 hidden"></div>
            </div>

            <!-- Video Captioner Card -->
            <div class="glass rounded-2xl p-8 flex flex-col h-full transform transition-all hover:scale-[1.02]">
                <div class="bg-purple-500/10 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><path d="M7 15h4M13 15h4M7 9h10"/></svg>
                </div>
                <h3 class="text-2xl font-bold mb-3">Auto-Captioner</h3>
                <p class="text-slate-400 mb-8 flex-grow">Automatically transcribe and burn captions into your videos using AI.</p>
                
                <form onsubmit="handleProcess(event, 'caption_video')" class="space-y-4">
                    <div class="relative group">
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-700 rounded-xl cursor-pointer hover:border-purple-500 hover:bg-purple-500/5 transition-all">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <span class="text-sm text-slate-400 group-hover:text-purple-400">Upload video file</span>
                            </div>
                            <input type="file" name="file" class="hidden" required accept="video/*" />
                        </label>
                    </div>
                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-500 text-white font-semibold py-3 rounded-xl transition-colors">Generate Captions</button>
                </form>
                <div id="status-caption_video" class="mt-4 hidden"></div>
            </div>

            <!-- Text Animator Card -->
            <div class="glass rounded-2xl p-8 flex flex-col h-full transform transition-all hover:scale-[1.02]">
                <div class="bg-pink-500/10 w-14 h-14 rounded-xl flex items-center justify-center mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-pink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 21 1.9-1.9M3 14c0-2.5 2-4.5 4.5-4.5h3c2.5 0 4.5 2 4.5 4.5v3c0 2.5-2 4.5-4.5 4.5h-3c-2.5 0-4.5-2-4.5-4.5v-3Z"/><path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/><path d="M17 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/><path d="M17 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/></svg>
                </div>
                <h3 class="text-2xl font-bold mb-3">Text Animator</h3>
                <p class="text-slate-400 mb-8 flex-grow">Turn your text into beautiful animated video clips automatically.</p>
                
                <form onsubmit="handleProcess(event, 'animate_text')" class="space-y-4">
                    <textarea name="text" maxlength="300" placeholder="Enter your text here... (max 300 chars)" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-sm focus:ring-2 focus:ring-pink-500 outline-none transition-all" rows="3" required></textarea>
                    <select name="style" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-sm focus:ring-2 focus:ring-pink-500 outline-none transition-all">
                        <option value="fade">Fade In</option>
                        <option value="typewriter">Typewriter</option>
                        <option value="slide">Slide In</option>
                        <option value="bounce">Bounce</option>
                    </select>
                    <button type="submit" class="w-full bg-pink-600 hover:bg-pink-500 text-white font-semibold py-3 rounded-xl transition-colors">Animate Text</button>
                </form>
                <div id="status-animate_text" class="mt-4 hidden"></div>
            </div>
        </div>
    </div>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        async function handleProcess(event, type) {
            event.preventDefault();
            const form = event.target;
            const btn = form.querySelector('button');
            const statusDiv = document.getElementById(`status-${type}`);
            const formData = new FormData(form);
            formData.append('type', type);

            // UI Reset
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            statusDiv.innerHTML = `
                <div class="flex items-center gap-3 text-blue-400">
                    <div class="loader w-5 h-5 border-2 border-transparent border-t-blue-500 rounded-full"></div>
                    <span>Processing media... please wait.</span>
                </div>
            `;
            statusDiv.classList.remove('hidden');

            try {
                const response = await fetch('{{ route("demo.upload") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    statusDiv.innerHTML = `
                        <div class="p-4 bg-green-500/10 border border-green-500/20 rounded-xl">
                            <p class="text-green-400 text-sm font-medium mb-3">✓ Processing Complete!</p>
                            <a href="${result.download_url}" class="inline-flex items-center text-sm font-bold text-white hover:underline">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                                Download Result
                            </a>
                        </div>
                    `;
                } else {
                    let errorMessage = result.error || result.message || 'Processing failed';
                    if (result.errors) {
                        errorMessage = Object.values(result.errors).flat().join('<br>');
                    }
                    throw new Error(errorMessage);
                }
            } catch (error) {
                statusDiv.innerHTML = `
                    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm">
                        ${error.message}
                    </div>
                `;
            }
 finally {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    </script>
</body>
</html>
