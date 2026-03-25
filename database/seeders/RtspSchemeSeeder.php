<?php

namespace Database\Seeders;

use App\Models\RtspScheme;
use Illuminate\Database\Seeder;

class RtspSchemeSeeder extends Seeder
{
    public function run(): void
    {
        $schemes = [
            'Hikvision' => 'rtsp://{username}:{password}@{ip}:{port}/h264/ch1/main/av_stream',
            'Dahua' => 'rtsp://{username}:{password}@{ip}:{port}/cam/realmonitor?channel=1&subtype=0',
            'Generic' => 'rtsp://{username}:{password}@{ip}:{port}/stream',
        ];

        foreach ($schemes as $name => $template) {
            RtspScheme::query()->updateOrCreate(
                ['name' => $name],
                ['scheme_template' => $template],
            );
        }
    }
}
