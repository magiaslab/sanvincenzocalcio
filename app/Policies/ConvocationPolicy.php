<?php

namespace App\Policies;

use App\Models\Convocation;
use App\Models\User;

class ConvocationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_convocation');
    }

    public function view(User $user, Convocation $convocation): bool
    {
        return $user->can('view_convocation');
    }

    public function create(User $user): bool
    {
        return $user->can('create_convocation');
    }

    public function update(User $user, Convocation $convocation): bool
    {
        return $user->can('update_convocation');
    }

    public function delete(User $user, Convocation $convocation): bool
    {
        return $user->can('delete_convocation');
    }
}

