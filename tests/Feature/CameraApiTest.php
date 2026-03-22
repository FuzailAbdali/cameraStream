<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
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

    public function test_it_lists_cameras_and_builds_stream_response(): void
    {
        $this->postJson('/api/cameras', [
            'name' => 'Backyard',
            'ip_address' => '192.168.1.11',
            'port' => 554,
            'username' => 'viewer',
            'password' => 'password',
        ])->assertCreated();

        $cameraId = \DB::table('cameras')->value('id');

        $this->getJson('/api/cameras')->assertOk()->assertJsonCount(1);
        $this->getJson("/api/cameras/{$cameraId}/stream")
            ->assertOk()
            ->assertJsonPath('status', 'queued');
    }
}
