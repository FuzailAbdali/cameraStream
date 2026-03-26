<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCameraRequest;
use App\Http\Requests\UpdateCameraRequest;
use App\Models\Camera;
use App\Models\RtspScheme;
use App\Services\StreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class CameraController extends Controller
{
    public function __construct(private readonly StreamService $streamService)
    {
    }

    public function index(): View
    {
        return view('cameras.index', [
            'cameras' => Camera::query()->with('rtspScheme')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('cameras.create', [
            'rtspSchemes' => RtspScheme::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCameraRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['rtsp_path'] = Camera::sanitizeRtspPath($request->string('rtsp_path')->toString());

        Camera::query()->create($data);

        return redirect()->route('cameras.index')->with('status', 'Camera created successfully.');
    }

    public function show(Camera $camera): View
    {
        $camera->load('rtspScheme');

        return view('cameras.show', [
            'camera' => $camera->fresh('rtspScheme'),
            'streamUrl' => $this->streamService->streamUrl($camera),
            'streamStatus' => $this->streamService->streamStatus($camera),
        ]);
    }

    public function edit(Camera $camera): View
    {
        return view('cameras.edit', [
            'camera' => $camera,
            'rtspSchemes' => RtspScheme::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCameraRequest $request, Camera $camera): RedirectResponse
    {
        $data = $request->validated();
        $data['rtsp_path'] = Camera::sanitizeRtspPath($request->string('rtsp_path')->toString());

        $camera->update($data);

        return redirect()->route('cameras.index')->with('status', 'Camera updated successfully.');
    }

    public function destroy(Camera $camera): RedirectResponse
    {
        $this->streamService->cleanupCameraStreams($camera);
        $camera->recordings()->delete();
        $camera->delete();

        return redirect()->route('cameras.index')->with('status', 'Camera deleted successfully.');
    }

    public function stream(Camera $id): RedirectResponse
    {
        return redirect()->route('cameras.show', $id);
    }

    public function startStream(Camera $id): JsonResponse
    {
        $streamUrl = $this->streamService->startStream($id);

        return response()->json([
            'status' => $this->streamService->streamStatus($id),
            'stream_status' => $this->streamService->streamStatus($id),
            'stream_url' => $streamUrl,
        ]);
    }

    public function streamStatus(Camera $id): JsonResponse
    {
        return response()->json([
            'status' => $this->streamService->streamStatus($id),
            'stream_url' => $this->streamService->streamUrl($id),
        ]);
    }

    public function debugStream(Camera $id): JsonResponse
    {
        $playlistPath = storage_path('app/public/streams/'.$id->id.'/index.m3u8');
        $logPath = storage_path('logs/laravel.log');
        $logs = [];

        if (File::exists($logPath)) {
            $lines = file($logPath) ?: [];
            $logs = array_slice($lines, -30);
        }

        return response()->json([
            'camera_id' => $id->id,
            'rtsp_url' => $id->buildRtspUrl(),
            'storage_path' => $playlistPath,
            'playlist_exists' => File::exists($playlistPath),
            'stream_status' => $this->streamService->streamStatus($id),
            'stream_url' => $this->streamService->streamUrl($id),
            'last_logs' => $logs,
        ]);
    }
}
