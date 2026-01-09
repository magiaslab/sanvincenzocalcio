<?php

namespace App\Policies;

use App\Models\KitItem;
use App\Models\User;

class KitItemPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_kit_item');
    }

    public function view(User $user, KitItem $kitItem): bool
    {
        return $user->can('view_kit_item');
    }

    public function create(User $user): bool
    {
        return $user->can('create_kit_item');
    }

    public function update(User $user, KitItem $kitItem): bool
    {
        return $user->can('update_kit_item');
    }

    public function delete(User $user, KitItem $kitItem): bool
    {
        return $user->can('delete_kit_item');
    }
}
