<?php

namespace Tests\Feature;

use App\Models\Camera;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CameraStreamPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_public_stream_directory_for_generated_playlists_and_segments(): void
    {
        Storage::fake('public');

        $camera = Camera::query()->create([
            'name' => 'Warehouse',
            'ip_address' => '192.168.1.20',
            'port' => 554,
            'username' => 'viewer',
            'password' => 'secret',
        ]);

        $this->assertSame("streams/{$camera->getKey()}", $camera->stream_directory);
        $this->assertSame("streams/{$camera->getKey()}/index.m3u8", $camera->stream_playlist);

        Storage::disk('public')->put($camera->stream_playlist, "#EXTM3U
#EXTINF:2.0,
segment_000.ts
");
        Storage::disk('public')->put($camera->stream_directory.'/segment_000.ts', 'segment-bytes');

        $this->get(route('cameras.playlist', $camera))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->assertSee(route('cameras.segment', [$camera, 'segment_000.ts']), false);

        $this->get(route('cameras.segment', [$camera, 'segment_000.ts']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'public, max-age=5')
            ->assertSee('segment-bytes');
    }
}
