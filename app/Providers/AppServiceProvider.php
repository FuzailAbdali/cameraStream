<?php

namespace App\Providers;

use App\Models\Camera;
use App\Policies\CameraPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Camera::class, CameraPolicy::class);
    }
}
