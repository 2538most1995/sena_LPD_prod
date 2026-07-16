<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Services\AccessScope;

class StudentPolicy
{
    public function __construct(private readonly AccessScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->status === 'active';
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function update(User $user, Student $student): bool
    {
        return $this->scope->canEditOwned($user, $student->created_by);
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->update($user, $student);
    }
}
