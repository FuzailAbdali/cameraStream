<?php

namespace Tests\Feature;

use App\Jobs\StartCameraStreamJob;
use App\Models\Camera;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CameraApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_camera_and_encrypts_password(): void
    {
        $payload = [
            'name' => 'Front Door',
            'ip_address' => '192.168.1.10',
            'external_ip' => '203.0.113.10',
            'port' => 554,
            'forwarded_port' => 8554,
            'username' => 'admin',
            'password' => 'secret-pass',
        ];

        $response = $this->postJson('/api/cameras', $payload);

        $response->assertCreated()
            ->assertJsonPath('name', 'Front Door')
            ->assertJsonMissingPath('password');

        $rawPassword = (string) \DB::table('cameras')->value('password');
        $this->assertNotSame('secret-pass', $rawPassword);
        $this->assertSame('secret-pass', Crypt::decryptString($rawPassword));
    }

    public function test_stream_endpoint_queues_the_job_until_a_playlist_exists(): void
    {
        Queue::fake();

        $camera = Camera::query()->create([
            'name' => 'Loading Dock',
            'ip_address' => '192.168.1.13',
            'port' => 554,
            'username' => 'viewer',
            'password' => 'password',
        ]);

        $this->getJson("/cameras/{$camera->getKey()}/stream")
            ->assertOk()
            ->assertJsonPath('status', 'queued')
            ->assertJsonPath('playlist_url', null);

        Queue::assertPushed(StartCameraStreamJob::class, fn (StartCameraStreamJob $job) => $job->cameraId === $camera->getKey());
    }

    public function test_stream_endpoint_returns_the_playlist_url_once_the_playlist_exists(): void
    {
        $camera = Camera::query()->create([
            'name' => 'Parking Lot',
            'ip_address' => '192.168.1.14',
            'port' => 554,
            'username' => 'viewer',
            'password' => 'password',
        ]);

        File::ensureDirectoryExists(public_path($camera->stream_directory));
        File::put(public_path($camera->stream_playlist), '#EXTM3U');

        $this->getJson("/cameras/{$camera->getKey()}/stream")
            ->assertOk()
            ->assertJsonPath('status', 'idle')
            ->assertJsonPath('playlist_url', route('cameras.playlist', $camera));
    }

    public function test_it_lists_cameras_and_builds_stream_response(): void
    {
        Queue::fake();

        $this->postJson('/api/cameras', [
            'name' => 'Backyard',
            'ip_address' => '192.168.1.11',
            'port' => 554,
            'username' => 'viewer',
            'password' => 'password',
        ])->assertCreated();

        $cameraId = \DB::table('cameras')->value('id');

        $this->getJson('/api/cameras')->assertOk()->assertJsonCount(1);
        $this->getJson("/cameras/{$cameraId}/stream")
            ->assertOk()
            ->assertJsonPath('status', 'queued');
    }
}
