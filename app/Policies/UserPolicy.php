<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'district_admin'], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $target): bool
    {
        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->role === 'district_admin'
            && $target->role === 'subdistrict_admin'
            && (int) $target->parent_id === (int) $user->id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->id !== $target->id && $this->update($user, $target);
    }
}
