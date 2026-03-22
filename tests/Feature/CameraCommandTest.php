<?php

namespace Tests\Feature;

use App\Jobs\StartCameraStreamJob;
use App\Models\Camera;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CameraCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_camera_start_command_queues_stream_job(): void
    {
        Queue::fake();

        $camera = Camera::query()->create([
            'name' => 'Garage',
            'ip_address' => '192.168.1.12',
            'port' => 554,
            'username' => 'operator',
            'password' => 'secret',
        ]);

        $this->artisan('camera:start', ['camera_id' => $camera->getKey()])
            ->assertSuccessful();

        Queue::assertPushed(StartCameraStreamJob::class, fn (StartCameraStreamJob $job) => $job->cameraId === $camera->getKey());
    }
}
