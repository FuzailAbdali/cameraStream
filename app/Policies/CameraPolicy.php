<?php

namespace App\Policies;

use App\Models\Camera;
use App\Models\User;

class CameraPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Camera $camera): bool
    {
        return true;
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function update(?User $user, Camera $camera): bool
    {
        return true;
    }

    public function delete(?User $user, Camera $camera): bool
    {
        return true;
    }

    public function stream(?User $user, Camera $camera): bool
    {
        return true;
    }
}
