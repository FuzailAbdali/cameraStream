<?php

namespace App\Services;

use App\Models\Camera;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class CameraStreamService
{
    public function ensureStreamDirectory(Camera $camera): string
    {
        $directory = $camera->stream_directory;
        Storage::disk('local')->makeDirectory($directory);

        return Storage::disk('local')->path($directory);
    }

    public function playlistPath(Camera $camera): string
    {
        return $this->ensureStreamDirectory($camera).'/index.m3u8';
    }

    public function start(Camera $camera): int
    {
        $outputDirectory = $this->ensureStreamDirectory($camera);
        $camera->forceFill([
            'stream_path' => $camera->stream_directory,
            'stream_status' => 'starting',
            'last_stream_started_at' => now(),
            'last_stream_error' => null,
        ])->save();

        Log::info('Starting camera stream.', ['camera_id' => $camera->getKey(), 'rtsp_url' => $camera->resolved_rtsp_url]);

        $process = new Process([
            'ffmpeg',
            '-rtsp_transport', 'tcp',
            '-i', $camera->resolved_rtsp_url,
            '-c:v', 'copy',
            '-c:a', 'aac',
            '-f', 'hls',
            '-hls_time', '2',
            '-hls_list_size', '5',
            '-hls_flags', 'delete_segments+append_list',
            '-hls_segment_filename', $outputDirectory.'/segment_%03d.ts',
            $outputDirectory.'/index.m3u8',
        ]);
        $process->setTimeout(null);

        $process->run(function (string $type, string $buffer) use ($camera): void {
            $camera->forceFill([
                'stream_status' => 'streaming',
                'last_stream_heartbeat_at' => now(),
            ])->save();

            Log::info('Camera stream output.', [
                'camera_id' => $camera->getKey(),
                'type' => $type,
                'message' => trim($buffer),
            ]);
        });

        if (! $process->isSuccessful()) {
            $camera->forceFill([
                'stream_status' => 'failed',
                'last_stream_failed_at' => now(),
                'last_stream_error' => $process->getErrorOutput() ?: $process->getOutput(),
            ])->save();

            Log::error('Camera stream failed.', [
                'camera_id' => $camera->getKey(),
                'exit_code' => $process->getExitCode(),
                'error' => $camera->last_stream_error,
            ]);

            return (int) $process->getExitCode();
        }

        $camera->forceFill([
            'stream_status' => 'stopped',
            'last_stream_heartbeat_at' => now(),
        ])->save();

        Log::info('Camera stream stopped cleanly.', ['camera_id' => $camera->getKey()]);

        return 0;
    }

    public function streamUrl(Camera $camera): ?string
    {
        if (! Storage::disk('local')->exists($camera->stream_playlist)) {
            return null;
        }

        return route('cameras.playlist', $camera);
    }
}
