<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCameraRequest;
use App\Http\Requests\UpdateCameraRequest;
use App\Jobs\StartCameraStreamJob;
use App\Models\Camera;
use App\Services\CameraStreamService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class CameraController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Camera::class);

        return response()->json(Camera::query()->latest()->get()->map(fn (Camera $camera) => $this->payload($camera)));
    }

    public function store(StoreCameraRequest $request): JsonResponse
    {
        $this->authorize('create', Camera::class);

        $camera = Camera::create($request->validated());

        return response()->json($this->payload($camera), 201);
    }

    public function update(UpdateCameraRequest $request, Camera $camera): JsonResponse
    {
        $this->authorize('update', $camera);

        $camera->update($request->validated());

        return response()->json($this->payload($camera->fresh()));
    }

    public function destroy(Camera $camera): JsonResponse
    {
        $this->authorize('delete', $camera);
        $camera->delete();

        return response()->json(status: 204);
    }

    public function stream(Camera $camera, CameraStreamService $streamService): JsonResponse
    {
        $this->authorize('stream', $camera);

        if ($camera->stream_status === 'idle' || $streamService->streamUrl($camera) === null) {
            StartCameraStreamJob::dispatch($camera->getKey());
            $camera->forceFill(['stream_status' => 'queued'])->save();
        }

        return response()->json([
            'camera' => $this->payload($camera->fresh()),
            'playlist_url' => $streamService->streamUrl($camera->fresh()),
            'status' => $camera->fresh()->stream_status,
        ]);
    }

    protected function payload(Camera $camera): array
    {
        return [
            'id' => $camera->getKey(),
            'name' => $camera->name,
            'ip_address' => $camera->ip_address,
            'external_ip' => $camera->external_ip,
            'port' => $camera->port,
            'forwarded_port' => $camera->forwarded_port,
            'username' => $camera->username,
            'rtsp_url' => $camera->rtsp_url,
            'resolved_rtsp_url' => $camera->resolved_rtsp_url,
            'stream_status' => $camera->stream_status,
            'stream_playlist_url' => route('cameras.playlist', $camera),
            'last_stream_started_at' => optional($camera->last_stream_started_at)?->toIso8601String(),
            'last_stream_heartbeat_at' => optional($camera->last_stream_heartbeat_at)?->toIso8601String(),
            'last_stream_failed_at' => optional($camera->last_stream_failed_at)?->toIso8601String(),
            'last_stream_error' => $camera->last_stream_error,
        ];
    }
}
