<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\Recording;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class StreamService
{
    public function startStream(Camera $camera): ?string
    {
        $this->ensureStreamDirectory($camera);
        $this->cleanupStreamSegments($camera);

        if ($this->isStreamProcessRunning($camera)) {
            return $this->streamUrl($camera);
        }

        $this->setStreamStatus($camera, 'starting');

        // This can be moved to queue for production.
        Process::start([
            'ffmpeg',
            '-y',
            '-rtsp_transport', 'tcp',
            '-i', $camera->rtsp_url,
            '-fflags', 'nobuffer',
            '-analyzeduration', '1000000',
            '-probesize', '1000000',
            '-c:v', 'copy',
            '-an',
            '-f', 'hls',
            '-hls_time', '2',
            '-hls_list_size', '6',
            '-hls_flags', 'delete_segments+independent_segments',
            '-hls_segment_filename', $this->segmentPathPattern($camera),
            $this->playlistPath($camera),
        ]);

        return $this->streamUrl($camera);
    }

    public function streamUrl(Camera $camera): ?string
    {
        $playlist = $this->streamDirectory($camera).'/index.m3u8';

        return Storage::disk('streams')->exists($playlist)
            ? Storage::disk('streams_public')->url($playlist)
            : null;
    }

    public function streamStatus(Camera $camera): string
    {
        if ($this->streamUrl($camera) !== null) {
            $this->setStreamStatus($camera, 'live');

            return 'live';
        }

        if ($this->isStreamProcessRunning($camera)) {
            $cached = Cache::get($this->streamStatusCacheKey($camera), 'starting');

            return in_array($cached, ['starting', 'live'], true) ? $cached : 'starting';
        }

        $this->setStreamStatus($camera, 'stopped');

        return 'stopped';
    }

    public function captureRecording(Camera $camera, int $seconds = 90): ?Recording
    {
        $directory = (string) $camera->id;
        Storage::disk('recordings')->makeDirectory($directory);

        $filename = now()->format('Ymd_His').'.mp4';
        $relativePath = $directory.'/'.$filename;

        $process = Process::timeout($seconds + 30)->run([
            'ffmpeg',
            '-y',
            '-rtsp_transport', 'tcp',
            '-i', $camera->rtsp_url,
            '-t', (string) $seconds,
            '-c:v', 'copy',
            '-c:a', 'aac',
            Storage::disk('recordings')->path($relativePath),
        ]);

        if (! $process->successful() || ! Storage::disk('recordings')->exists($relativePath)) {
            return null;
        }

        return Recording::query()->create([
            'camera_id' => $camera->id,
            'file_path' => $relativePath,
            'duration' => $seconds,
            'created_at' => now(),
        ]);
    }

    public function cleanupCameraStreams(Camera $camera): void
    {
        Storage::disk('streams')->deleteDirectory($this->streamDirectory($camera));
        Cache::forget($this->streamStatusCacheKey($camera));
    }

    private function cleanupStreamSegments(Camera $camera): void
    {
        $directory = $this->streamDirectory($camera);

        if (Storage::disk('streams')->exists($directory)) {
            Storage::disk('streams')->deleteDirectory($directory);
        }

        Storage::disk('streams')->makeDirectory($directory);
    }

    private function streamDirectory(Camera $camera): string
    {
        return (string) $camera->id;
    }

    private function ensureStreamDirectory(Camera $camera): void
    {
        Storage::disk('streams')->makeDirectory($this->streamDirectory($camera));
    }

    private function playlistPath(Camera $camera): string
    {
        return Storage::disk('streams')->path($this->streamDirectory($camera).'/index.m3u8');
    }

    private function segmentPathPattern(Camera $camera): string
    {
        return Storage::disk('streams')->path($this->streamDirectory($camera).'/segment_%03d.ts');
    }

    private function isStreamProcessRunning(Camera $camera): bool
    {
        $process = Process::run([
            'pgrep',
            '-f',
            'ffmpeg.*'.$this->playlistPath($camera),
        ]);

        return $process->successful() && trim($process->output()) !== '';
    }

    private function streamStatusCacheKey(Camera $camera): string
    {
        return 'camera:'.$camera->id.':stream_status';
    }

    private function setStreamStatus(Camera $camera, string $status): void
    {
        Cache::put($this->streamStatusCacheKey($camera), $status, now()->addMinutes(30));
    }
}
