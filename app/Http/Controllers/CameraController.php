<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCameraRequest;
use App\Http\Requests\UpdateCameraRequest;
use App\Models\Camera;
use App\Services\StreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CameraController extends Controller
{
    public function __construct(private readonly StreamService $streamService)
    {
    }

    public function index(): View
    {
        return view('cameras.index', [
            'cameras' => Camera::query()->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('cameras.create');
    }

    public function store(StoreCameraRequest $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'external_ip' => 'nullable|ip',
            'port' => 'required|integer|between:1,65535',
            'rtsp_path' => 'nullable|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        $data['rtsp_path'] = Camera::sanitizeRtspPath($request->string('rtsp_path')->toString());

        Camera::query()->create($data);

        return redirect()->route('cameras.index')->with('status', 'Camera created successfully.');
    }

    public function show(Camera $camera): View
    {
        return view('cameras.show', [
            'camera' => $camera->fresh(),
            'streamUrl' => $this->streamService->streamUrl($camera),
            'streamStatus' => $this->streamService->streamStatus($camera),
        ]);
    }

    public function edit(Camera $camera): View
    {
        return view('cameras.edit', compact('camera'));
    }

    public function update(UpdateCameraRequest $request, Camera $camera): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'external_ip' => 'nullable|ip',
            'port' => 'required|integer|between:1,65535',
            'rtsp_path' => 'nullable|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
        ]);

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
        $this->streamService->startStream($id);

        return response()->json([
            'status' => 'started',
            'stream_status' => $this->streamService->streamStatus($id),
            'stream_url' => $this->streamService->streamUrl($id),
        ]);
    }

    public function streamStatus(Camera $id): JsonResponse
    {
        return response()->json([
            'status' => $this->streamService->streamStatus($id),
            'stream_url' => $this->streamService->streamUrl($id),
        ]);
    }
}
