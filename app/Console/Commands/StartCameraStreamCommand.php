<?php

namespace App\Console\Commands;

use App\Jobs\StartCameraStreamJob;
use App\Models\Camera;
use Illuminate\Console\Command;

class StartCameraStreamCommand extends Command
{
    protected $signature = 'camera:start {camera_id}';

    protected $description = 'Queue a background job to convert an RTSP feed into HLS.';

    public function handle(): int
    {
        $camera = Camera::findOrFail($this->argument('camera_id'));

        StartCameraStreamJob::dispatch($camera->getKey());

        $this->info("Camera {$camera->name} stream job queued.");

        return self::SUCCESS;
    }
}
