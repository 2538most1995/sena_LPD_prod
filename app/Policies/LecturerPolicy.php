<?php

namespace App\Policies;

use App\Models\Lecturer;
use App\Models\User;
use App\Services\AccessScope;

class LecturerPolicy
{
    public function __construct(private readonly AccessScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, Lecturer $lecturer): bool
    {
        return $this->scope->canViewOwned($user, $lecturer->created_by);
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function update(User $user, Lecturer $lecturer): bool
    {
        return $this->scope->canEditOwned($user, $lecturer->created_by);
    }

    public function delete(User $user, Lecturer $lecturer): bool
    {
        return $this->update($user, $lecturer);
    }
}
