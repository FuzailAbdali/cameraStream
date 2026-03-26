@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Stream: {{ $camera->name }}</h1>
    <a href="{{ route('cameras.index') }}" class="btn btn-secondary">Back</a>
</div>

<div class="mb-3">
    <label class="form-label"><strong>Source RTSP (debug)</strong></label>
    <input type="text" class="form-control" value="{{ $camera->rtsp_url }}" readonly>
</div>

<div class="card p-3" data-stream-container
     data-start-url="{{ route('cameras.start-stream', $camera) }}"
     data-status-url="{{ route('cameras.stream-status', $camera) }}"
     data-initial-url="{{ $streamUrl ?? '' }}"
     data-initial-status="{{ $streamStatus }}">

    <div class="d-flex align-items-center gap-2 mb-3">
        <button id="start-stream-btn" type="button" class="btn btn-primary">Start Stream</button>
        <span id="stream-status" class="badge text-bg-secondary">Stopped</span>
    </div>

    <video id="hls-video" class="w-100 rounded d-none" controls autoplay muted playsinline style="background:#000; min-height: 360px;"></video>
    <div id="stream-loading" class="alert alert-info mb-0">Click "Start Stream" to begin live video.</div>
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = @json(csrf_token());
    const container = document.querySelector('[data-stream-container]');
    const startButton = document.getElementById('start-stream-btn');
    const statusBadge = document.getElementById('stream-status');
    const video = document.getElementById('hls-video');
    const loading = document.getElementById('stream-loading');

    let currentPlaylistUrl = container.dataset.initialUrl || null;
    let hlsPlayer = null;

    function setStatus(status) {
        const value = (status || 'stopped').toLowerCase();
        statusBadge.classList.remove('text-bg-secondary', 'text-bg-warning', 'text-bg-success', 'text-bg-danger');

        if (value === 'live') {
            statusBadge.classList.add('text-bg-success');
            statusBadge.textContent = 'Live';
            loading.classList.add('d-none');
            return;
        }

        if (value === 'starting') {
            statusBadge.classList.add('text-bg-warning');
            statusBadge.textContent = 'Starting...';
            loading.classList.remove('d-none');
            loading.textContent = 'Starting stream...';
            return;
        }

        if (value === 'failed') {
            statusBadge.classList.add('text-bg-danger');
            statusBadge.textContent = 'Failed';
            loading.classList.remove('d-none');
            loading.textContent = 'Stream failed to start. Check debug endpoint and logs.';
            return;
        }

        statusBadge.classList.add('text-bg-secondary');
        statusBadge.textContent = 'Stopped';
        loading.classList.remove('d-none');
        loading.textContent = 'Click "Start Stream" to begin live video.';
    }

    async function isPlaylistReady(url) {
        try {
            const response = await fetch(url, { method: 'GET', cache: 'no-store' });
            return response.ok;
        } catch (error) {
            return false;
        }
    }

    async function attachPlayer(url) {
        if (!url || video.dataset.loadedUrl === url) {
            return;
        }

        const ready = await isPlaylistReady(url);
        if (!ready) {
            return;
        }

        video.classList.remove('d-none');
        video.dataset.loadedUrl = url;

        if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = url;
            video.play().catch(() => {});
            return;
        }

        if (window.Hls && Hls.isSupported()) {
            if (hlsPlayer) {
                hlsPlayer.destroy();
            }

            hlsPlayer = new Hls();
            hlsPlayer.loadSource(url);
            hlsPlayer.attachMedia(video);
            hlsPlayer.on(Hls.Events.MANIFEST_PARSED, () => {
                video.play().catch(() => {});
            });
        }
    }

    let pollTimer = null;

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function startPolling() {
        if (pollTimer) {
            return;
        }

        pollTimer = setInterval(() => {
            pollStatus().catch(() => {
                if (!currentPlaylistUrl) {
                    setStatus('stopped');
                }
            });
        }, 5000);
    }

    async function pollStatus() {
        const response = await fetch(container.dataset.statusUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Unable to fetch stream status');
        }

        const data = await response.json();
        const status = (data.status || '').toLowerCase();
        setStatus(status);

        if (status === 'live' && data.stream_url) {
            currentPlaylistUrl = data.stream_url;
            await attachPlayer(currentPlaylistUrl);
            stopPolling();
        }

        if (status === 'failed') {
            stopPolling();
        }
    }

    async function startStream() {
        startPolling();
        startButton.disabled = true;
        setStatus('starting');

        try {
            const response = await fetch(container.dataset.startUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({}),
            });

            if (!response.ok) {
                throw new Error('Unable to start stream');
            }

            const data = await response.json();
            const status = (data.stream_status || data.status || '').toLowerCase();
            setStatus(status);

            if (status === 'live' && data.stream_url) {
                currentPlaylistUrl = data.stream_url;
                await attachPlayer(currentPlaylistUrl);
                stopPolling();
            }

            if (status === 'failed') {
                stopPolling();
            }
        } catch (error) {
            setStatus('stopped');
        } finally {
            startButton.disabled = false;
        }
    }

    startButton.addEventListener('click', startStream);

    setStatus(container.dataset.initialStatus);
    if ((container.dataset.initialStatus || '').toLowerCase() === 'live' && currentPlaylistUrl) {
        attachPlayer(currentPlaylistUrl);
    }

    startPolling();
</script>
@endpush
