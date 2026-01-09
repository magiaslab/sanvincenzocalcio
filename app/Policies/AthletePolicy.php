<?php

namespace App\Policies;

use App\Models\Athlete;
use App\Models\User;

class AthletePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_athlete');
    }

    public function view(User $user, Athlete $athlete): bool
    {
        return $user->can('view_athlete');
    }

    public function create(User $user): bool
    {
        return $user->can('create_athlete');
    }

    public function update(User $user, Athlete $athlete): bool
    {
        return $user->can('update_athlete');
    }

    public function delete(User $user, Athlete $athlete): bool
    {
        return $user->can('delete_athlete');
    }
}
