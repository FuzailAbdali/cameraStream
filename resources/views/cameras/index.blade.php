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
        .meta { color: #93c5fd; margin-bottom: 1rem; word-break: break-all; }
        .status-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin: .75rem 0 1rem; }
        .badge { display:inline-block; padding:.25rem .65rem; background:#2563eb; border-radius:999px; font-size:.875rem; }
        .message { color: #d1d5db; font-size: .875rem; margin: 0; }
        video { width: 100%; border-radius: 12px; background: #000; min-height: 220px; }
    </style>
</head>
<body>
    <h1>IP Camera Management</h1>
    <p>Manage RTSP sources, queue FFmpeg HLS transcoding, and preview live playlists generated in public storage.</p>

    <div class="grid">
        @foreach ($cameras as $camera)
            <section class="card">
                <h2>{{ $camera->name }}</h2>
                <div class="meta">{{ $camera->resolved_rtsp_url }}</div>
                <div class="status-row">
                    <div class="badge" data-status-badge="{{ $camera->id }}">Status: {{ $camera->stream_status }}</div>
                    <p class="message" data-status-message="{{ $camera->id }}">Preparing stream…</p>
                </div>
                <video id="camera-{{ $camera->id }}" controls muted playsinline autoplay></video>
            </section>
        @endforeach
    </div>

    <script>
        @php
            $cameraStreams = $cameras->map(function ($camera) {
                return [
                    'id' => $camera->id,
                    'streamEndpoint' => url("/api/cameras/{$camera->id}/stream"),
                    'initialStatus' => $camera->stream_status,
                ];
            })->values()->all();
        @endphp

        const cameras = @json($cameraStreams);
        const pollDelayMs = 3000;

        const attachStream = (video, playlistUrl) => {
            if (video.dataset.loadedPlaylist === playlistUrl) {
                return;
            }

            video.dataset.loadedPlaylist = playlistUrl;

            if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = playlistUrl;
                video.play().catch(() => {});
                return;
            }

            if (window.Hls && Hls.isSupported()) {
                if (video.hlsInstance) {
                    video.hlsInstance.destroy();
                }

                const hls = new Hls({
                    lowLatencyMode: true,
                    liveSyncDurationCount: 3,
                });
                hls.loadSource(playlistUrl);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    video.play().catch(() => {});
                });
                hls.on(Hls.Events.ERROR, (_, data) => {
                    console.error('HLS playback error', data);
                });
                video.hlsInstance = hls;
            }
        };

        const setStatus = (cameraId, status, message) => {
            const badge = document.querySelector(`[data-status-badge="${cameraId}"]`);
            const text = document.querySelector(`[data-status-message="${cameraId}"]`);

            if (badge && status) {
                badge.textContent = `Status: ${status}`;
            }

            if (text && message) {
                text.textContent = message;
            }
        };

        const startStream = async (camera) => {
            const video = document.getElementById(`camera-${camera.id}`);
            if (!video) {
                return;
            }

            setStatus(camera.id, camera.initialStatus, 'Requesting stream…');

            try {
                const response = await fetch(camera.streamEndpoint, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                const payload = await response.json();
                const status = payload.status ?? payload.camera?.stream_status ?? 'unknown';
                const playlistUrl = payload.playlist_url;

                if (playlistUrl) {
                    attachStream(video, playlistUrl);
                    setStatus(camera.id, status, 'Live stream connected.');
                    return;
                }

                setStatus(camera.id, status, 'Waiting for playlist to be generated…');
                window.setTimeout(() => startStream({
                    ...camera,
                    initialStatus: status,
                }), pollDelayMs);
            } catch (error) {
                setStatus(camera.id, 'error', error.message);
            }
        };

        cameras.forEach(startStream);
    </script>
</body>
</html>
