@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Stream: {{ $camera->name }}</h1>
    <a href="{{ route('cameras.index') }}" class="btn btn-secondary">Back</a>
</div>

<p><strong>Source RTSP:</strong> {{ $camera->rtsp_url }}</p>

<div class="card p-3">
    <video id="hls-video" class="w-100 rounded" controls autoplay muted playsinline style="background:#000; min-height: 360px;"></video>
    <div id="stream-loading" class="alert alert-info mt-3 mb-0 {{ $streamUrl ? 'd-none' : '' }}">Preparing stream... please refresh in a few seconds if this persists.</div>
</div>
@endsection

@push('scripts')
<script>
    const playlistUrl = @json($streamUrl);
    const video = document.getElementById('hls-video');
    const loading = document.getElementById('stream-loading');

    if (playlistUrl) {
        if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = playlistUrl;
        } else if (window.Hls && Hls.isSupported()) {
            const hls = new Hls();
            hls.loadSource(playlistUrl);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, () => {
                loading.classList.add('d-none');
                video.play().catch(() => {});
            });
        }
    }
</script>
@endpush
