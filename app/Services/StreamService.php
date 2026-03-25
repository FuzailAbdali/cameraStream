<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\Recording;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class StreamService
{
    public function startStream(Camera $camera): ?string
    {
        $directory = $this->streamDirectory($camera);
        Storage::disk('streams')->makeDirectory($directory);
        $this->cleanupStreamSegments($camera);

        $output = Storage::disk('streams')->path($directory.'/index.m3u8');

        Process::forever()->run([
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
            '-hls_segment_filename', Storage::disk('streams')->path($directory.'/segment_%03d.ts'),
            $output,
        ]);

        $playlist = $directory.'/index.m3u8';

        return Storage::disk('streams')->exists($playlist)
            ? Storage::disk('streams_public')->url($playlist)
            : null;
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
}
