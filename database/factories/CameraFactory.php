<?php

namespace Database\Factories;

use App\Models\Camera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Camera>
 */
class CameraFactory extends Factory
{
    protected $model = Camera::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'ip_address' => fake()->ipv4(),
            'external_ip' => fake()->optional()->ipv4(),
            'port' => 554,
            'forwarded_port' => fake()->optional()->numberBetween(1024, 65535),
            'username' => fake()->userName(),
            'password' => 'secret',
            'rtsp_url' => null,
            'stream_status' => 'idle',
        ];
    }
}
