<?php

namespace App\Policies;

use App\Models\Field;
use App\Models\User;

class FieldPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_field');
    }

    public function view(User $user, Field $field): bool
    {
        return $user->can('view_field');
    }

    public function create(User $user): bool
    {
        return $user->can('create_field');
    }

    public function update(User $user, Field $field): bool
    {
        return $user->can('update_field');
    }

    public function delete(User $user, Field $field): bool
    {
        return $user->can('delete_field');
    }
}
