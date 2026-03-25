<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCameraRequest;
use App\Http\Requests\UpdateCameraRequest;
use App\Models\Camera;
use App\Services\StreamService;
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
        Camera::query()->create($request->validated());

        return redirect()->route('cameras.index')->with('status', 'Camera created successfully.');
    }

    public function show(Camera $camera): View
    {
        $streamUrl = $this->streamService->startStream($camera);
        $this->streamService->captureRecording($camera);

        return view('cameras.show', [
            'camera' => $camera->fresh(),
            'streamUrl' => $streamUrl,
        ]);
    }

    public function edit(Camera $camera): View
    {
        return view('cameras.edit', compact('camera'));
    }

    public function update(UpdateCameraRequest $request, Camera $camera): RedirectResponse
    {
        $camera->update($request->validated());

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
}
