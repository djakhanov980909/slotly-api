<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function create(User $user): bool
    {
        return $user->role === Role::Admin;
    }

    public function update(User $user, Service $service): bool
    {
        return $user->role === Role::Admin;
    }
}
