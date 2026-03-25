<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\Recording;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StreamService
{
    public function startStream(Camera $camera): ?string
    {
        $this->ensureStreamDirectory($camera);
        $this->cleanupStreamSegments($camera);

        if ($this->hasStreamLock($camera)) {
            return $this->streamUrl($camera);
        }

        $this->setStreamLock($camera);
        $this->setStreamStatus($camera, 'starting');

        try {
            Process::start([
                'ffmpeg',
                '-y',
                '-rtsp_transport', 'tcp',
                '-i', $camera->buildRtspUrl(),
                '-fflags', 'nobuffer',
                '-analyzeduration', '1000000',
                '-probesize', '1000000',
                '-map', '0:v:0',
                '-map', '0:a?',
                '-c:v', 'libx264',
                '-preset', 'veryfast',
                '-tune', 'zerolatency',
                '-pix_fmt', 'yuv420p',
                '-c:a', 'aac',
                '-ar', '44100',
                '-ac', '1',
                '-b:a', '128k',
                '-f', 'hls',
                '-hls_time', '2',
                '-hls_list_size', '6',
                '-hls_flags', 'delete_segments+independent_segments',
                '-hls_segment_filename', $this->segmentPathPattern($camera),
                $this->playlistPath($camera),
            ]);
        } catch (Throwable $exception) {
            $this->releaseStreamLock($camera);
            $this->setStreamStatus($camera, 'stopped');

            Log::error('Failed to start camera stream.', [
                'camera_id' => $camera->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

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
            $this->setStreamLock($camera);
            $this->setStreamStatus($camera, 'live');

            return 'live';
        }

        if ($this->hasStreamLock($camera)) {
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

        $filename = now()->format('Ymd_His_u').'_'.uniqid().'.mp4';
        $relativePath = $directory.'/'.$filename;

        try {
            $process = Process::timeout($seconds + 30)->run([
                'ffmpeg',
                '-y',
                '-rtsp_transport', 'tcp',
                '-i', $camera->buildRtspUrl(),
                '-map', '0:v:0',
                '-map', '0:a?',
                '-t', (string) $seconds,
                '-c:v', 'libx264',
                '-preset', 'veryfast',
                '-pix_fmt', 'yuv420p',
                '-c:a', 'aac',
                '-movflags', '+faststart',
                Storage::disk('recordings')->path($relativePath),
            ]);
        } catch (Throwable $exception) {
            Log::error('FFmpeg recording command failed to run.', [
                'camera_id' => $camera->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $process->successful() || ! Storage::disk('recordings')->exists($relativePath)) {
            Log::warning('FFmpeg recording did not produce output.', [
                'camera_id' => $camera->id,
                'exit_code' => $process->exitCode(),
                'error_output' => $process->errorOutput(),
            ]);

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
        $this->releaseStreamLock($camera);
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

    private function streamStatusCacheKey(Camera $camera): string
    {
        return 'camera:'.$camera->id.':stream_status';
    }

    private function streamLockCacheKey(Camera $camera): string
    {
        return 'camera:'.$camera->id.':stream_lock';
    }

    private function setStreamLock(Camera $camera): void
    {
        Cache::put($this->streamLockCacheKey($camera), true, now()->addSeconds(45));
    }

    private function hasStreamLock(Camera $camera): bool
    {
        return Cache::has($this->streamLockCacheKey($camera));
    }

    private function releaseStreamLock(Camera $camera): void
    {
        Cache::forget($this->streamLockCacheKey($camera));
    }

    private function setStreamStatus(Camera $camera, string $status): void
    {
        Cache::put($this->streamStatusCacheKey($camera), $status, now()->addMinutes(30));
    }
}
