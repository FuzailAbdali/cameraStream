<?php

namespace Database\Seeders;

use App\Models\Camera;
use App\Models\RtspScheme;
use Illuminate\Database\Seeder;

class CameraSeeder extends Seeder
{
    public function run(): void
    {
        Camera::query()->firstOrCreate(
            ['name' => 'Default Lobby Camera'],
            [
                'ip_address' => '192.168.1.20',
                'external_ip' => '203.0.113.20',
                'port' => 554,
                'rtsp_path' => 'stream',
                'rtsp_scheme_id' => RtspScheme::query()->where('name', 'Generic')->value('id'),
                'username' => 'viewer',
                'password' => 'secret',
            ],
        );
    }
}
