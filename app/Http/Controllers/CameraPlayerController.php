<?php

namespace App\Http\Controllers;

use App\Jobs\StartCameraStreamJob;
use App\Models\Camera;
use App\Services\CameraStreamService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class CameraPlayerController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Camera::class);

        return view('cameras.index', [
            'cameras' => Camera::query()->latest()->get(),
        ]);
    }

    public function stream(Camera $camera, CameraStreamService $streamService): JsonResponse
    {
        $this->authorize('stream', $camera);

        if ($camera->stream_status === 'idle' || $streamService->streamUrl($camera) === null) {
            StartCameraStreamJob::dispatch($camera->getKey());
            $camera->forceFill(['stream_status' => 'queued'])->save();
        }

        $camera = $camera->fresh();

        return response()->json([
            'playlist_url' => $streamService->streamUrl($camera),
            'status' => $camera->stream_status,
        ]);
    }

    public function playlist(Camera $camera)
    {
        $this->authorize('stream', $camera);

        $path = $this->streamPath($camera, 'index.m3u8');
        abort_unless(File::exists($path), 404);

        $content = File::get($path);
        $content = preg_replace_callback('/segment_(\d+)\.ts/', function (array $matches) use ($camera): string {
            return route('cameras.segment', [$camera, $matches[0]]);
        }, $content);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function segment(Camera $camera, string $file)
    {
        $this->authorize('stream', $camera);

        $path = $this->streamPath($camera, $file);
        abort_unless(File::exists($path), 404);

        return response(File::get($path), 200, [
            'Content-Type' => 'video/mp2t',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=5',
        ]);
    }

    protected function streamPath(Camera $camera, string $file): string
    {
        return public_path(trim($camera->stream_directory.'/'.$file, '/'));
    }
}
