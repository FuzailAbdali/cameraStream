<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
