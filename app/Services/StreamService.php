<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\Recording;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class StreamService
{
    public function startStream(Camera $camera): ?string
    {
        $this->ensureStreamDirectory($camera);
        $this->cleanupStreamSegments($camera);

        $this->setStreamStatus($camera, 'starting');

        $rtspUrl = $camera->buildRtspUrl();
        $outputPath = $this->streamStoragePath($camera);
        $playlistPath = $this->playlistPath($camera);

        Log::info("Starting stream for camera {$camera->id}");
        Log::info('RTSP URL: '.$rtspUrl);
        Log::info('Output path: '.$outputPath);

        $process = new Process([
            'ffmpeg',
            '-rtsp_transport', 'tcp',
            '-i', $rtspUrl,
            '-c:v', 'libx264',
            '-preset', 'veryfast',
            '-tune', 'zerolatency',
            '-c:a', 'aac',
            '-f', 'hls',
            '-hls_time', '2',
            '-hls_list_size', '3',
            '-hls_flags', 'delete_segments',
            $playlistPath,
        ]);

        $process->start();

        if ($this->waitForStreamReadiness($camera, 8, 500000)) {
            $this->setStreamStatus($camera, 'live');
            Cache::put($this->streamPidCacheKey($camera), $process->getPid(), now()->addMinutes(30));

            Log::info("Stream is live for camera {$camera->id}");

            return $this->streamUrl($camera);
        }

        if (! $process->isRunning()) {
            Log::error('FFmpeg failed: '.$process->getErrorOutput());
        }

        $this->setStreamStatus($camera, 'failed');

        return null;
    }

    public function streamUrl(Camera $camera): ?string
    {
        return $this->isStreamReady($camera)
            ? Storage::disk('public')->url($this->streamRelativeDirectory($camera).'/index.m3u8')
            : null;
    }

    public function streamStatus(Camera $camera): string
    {
        if ($this->isStreamReady($camera)) {
            $this->setStreamStatus($camera, 'live');

            return 'live';
        }

        $status = Cache::get($this->streamStatusCacheKey($camera), 'stopped');

        if ($status === 'live' && ! $this->waitForStreamReadiness($camera, 2, 300000)) {
            $this->setStreamStatus($camera, 'failed');

            return 'failed';
        }

        return in_array($status, ['starting', 'live', 'failed', 'stopped'], true) ? $status : 'stopped';
    }

    public function isStreamReady(Camera $camera): bool
    {
        return File::exists($this->playlistPath($camera));
    }

    public function captureRecording(Camera $camera, int $seconds = 90): ?Recording
    {
        $directory = $this->recordingStoragePath($camera);
        File::makeDirectory($directory, 0755, true, true);

        $filename = now()->format('Ymd_His_u').'.mp4';
        $relativePath = $this->recordingRelativeDirectory($camera).'/'.$filename;

        $process = new Process([
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
            Storage::disk('public')->path($relativePath),
        ]);

        $process->run();

        if (! $process->isSuccessful() || ! Storage::disk('public')->exists($relativePath)) {
            Log::warning('FFmpeg recording did not produce output.', [
                'camera_id' => $camera->id,
                'error_output' => $process->getErrorOutput(),
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
        File::deleteDirectory($this->streamStoragePath($camera));
        Cache::forget($this->streamStatusCacheKey($camera));
        Cache::forget($this->streamPidCacheKey($camera));
    }

    private function waitForStreamReadiness(Camera $camera, int $maxAttempts = 5, int $sleepMicros = 500000): bool
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            if ($this->isStreamReady($camera)) {
                return true;
            }

            usleep($sleepMicros);
        }

        return false;
    }

    private function cleanupStreamSegments(Camera $camera): void
    {
        File::deleteDirectory($this->streamStoragePath($camera));
        File::makeDirectory($this->streamStoragePath($camera), 0755, true, true);
    }

    private function ensureStreamDirectory(Camera $camera): void
    {
        File::makeDirectory($this->streamStoragePath($camera), 0755, true, true);
    }

    private function streamRelativeDirectory(Camera $camera): string
    {
        return 'streams/'.(string) $camera->id;
    }

    private function recordingRelativeDirectory(Camera $camera): string
    {
        return 'recordings/'.(string) $camera->id;
    }

    private function streamStoragePath(Camera $camera): string
    {
        return storage_path('app/public/'.$this->streamRelativeDirectory($camera));
    }

    private function recordingStoragePath(Camera $camera): string
    {
        return storage_path('app/public/'.$this->recordingRelativeDirectory($camera));
    }

    private function playlistPath(Camera $camera): string
    {
        return $this->streamStoragePath($camera).'/index.m3u8';
    }

    private function streamStatusCacheKey(Camera $camera): string
    {
        return 'camera:'.$camera->id.':stream_status';
    }

    private function streamPidCacheKey(Camera $camera): string
    {
        return 'camera:'.$camera->id.':stream_pid';
    }

    private function setStreamStatus(Camera $camera, string $status): void
    {
        Cache::put($this->streamStatusCacheKey($camera), $status, now()->addMinutes(30));
    }
}
