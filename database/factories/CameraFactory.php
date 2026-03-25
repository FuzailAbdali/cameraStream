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
            'username' => fake()->userName(),
            'password' => 'secret',
        ];
    }
}
