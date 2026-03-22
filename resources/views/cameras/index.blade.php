<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Camera Management</title>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background: #111827; color: #f9fafb; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; }
        .card { background: #1f2937; border-radius: 16px; padding: 1.25rem; box-shadow: 0 10px 30px rgba(0,0,0,.3); }
        .meta { color: #93c5fd; margin-bottom: 1rem; }
        video { width: 100%; border-radius: 12px; background: #000; min-height: 220px; }
        .badge { display:inline-block; padding:.25rem .65rem; background:#2563eb; border-radius:999px; font-size:.875rem; }
    </style>
</head>
<body>
    <h1>IP Camera Management</h1>
    <p>Manage RTSP sources, queue FFmpeg HLS transcoding, and preview live playlists generated in local storage.</p>

    <div class="grid">
        @foreach ($cameras as $camera)
            <section class="card">
                <h2>{{ $camera->name }}</h2>
                <div class="meta">{{ $camera->resolved_rtsp_url }}</div>
                <div class="badge">Status: {{ $camera->stream_status }}</div>
                <video id="camera-{{ $camera->id }}" controls muted playsinline></video>
            </section>
        @endforeach
    </div>

    <script>
        @php
            $cameraPlaylists = $cameras->map(function ($camera) {
                return [
                    'id' => $camera->id,
                    'playlist' => route('cameras.playlist', $camera),
                ];
            })->values()->all();
        @endphp

        const cameras = @json($cameraPlaylists);

        cameras.forEach((camera) => {
            const video = document.getElementById(`camera-${camera.id}`);
            if (!video) return;

            if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = camera.playlist;
                return;
            }

            if (window.Hls && Hls.isSupported()) {
                const hls = new Hls();
                hls.loadSource(camera.playlist);
                hls.attachMedia(video);
            }
        });
    </script>
</body>
</html>
