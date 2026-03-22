<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Services\CameraStreamService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class StartCameraStreamJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 0;

    public function __construct(public int $cameraId)
    {
    }

    public function handle(CameraStreamService $streamService): void
    {
        $camera = Camera::findOrFail($this->cameraId);

        Log::info('Dispatching camera stream job.', ['camera_id' => $camera->getKey()]);
        $streamService->start($camera);
    }
}
