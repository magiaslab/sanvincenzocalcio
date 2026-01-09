<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_team');
    }

    public function view(User $user, Team $team): bool
    {
        return $user->can('view_team');
    }

    public function create(User $user): bool
    {
        return $user->can('create_team');
    }

    public function update(User $user, Team $team): bool
    {
        return $user->can('update_team');
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->can('delete_team');
    }
}
