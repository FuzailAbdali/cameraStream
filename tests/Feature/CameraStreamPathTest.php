<?php

namespace Tests\Feature;

use App\Models\Camera;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CameraStreamPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_private_stream_directory_for_generated_playlists_and_segments(): void
    {
        Storage::fake('local');

        $camera = Camera::query()->create([
            'name' => 'Warehouse',
            'ip_address' => '192.168.1.20',
            'port' => 554,
            'username' => 'viewer',
            'password' => 'secret',
        ]);

        $this->assertSame("streams/{$camera->getKey()}", $camera->stream_directory);
        $this->assertSame("streams/{$camera->getKey()}/index.m3u8", $camera->stream_playlist);

        Storage::disk('local')->put($camera->stream_playlist, "#EXTM3U\n#EXTINF:2.0,\nsegment_000.ts\n");
        Storage::disk('local')->put($camera->stream_directory.'/segment_000.ts', 'segment-bytes');

        $this->get(route('cameras.playlist', $camera))
            ->assertOk()
            ->assertSee(route('cameras.segment', [$camera, 'segment_000.ts']), false);

        $this->get(route('cameras.segment', [$camera, 'segment_000.ts']))
            ->assertOk()
            ->assertSee('segment-bytes');
    }
}
