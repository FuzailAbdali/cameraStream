<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class CameraPlayerController extends Controller
{
    use AuthorizesRequests;

    protected string $disk = 'public';

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

        abort_unless(Storage::disk($this->disk)->exists($camera->stream_playlist), 404);

        $content = Storage::disk($this->disk)->get($camera->stream_playlist);
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

        $path = trim($camera->stream_directory."/{$file}", '/');
        abort_unless(Storage::disk($this->disk)->exists($path), 404);

        return response(Storage::disk($this->disk)->get($path), 200, [
            'Content-Type' => 'video/mp2t',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=5',
        ]);
    }
}
