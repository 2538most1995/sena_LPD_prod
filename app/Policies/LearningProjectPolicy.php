<?php

namespace App\Policies;

use App\Models\LearningProject;
use App\Models\User;
use App\Services\AccessScope;

class LearningProjectPolicy
{
    public function __construct(private readonly AccessScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, LearningProject $project): bool
    {
        return $this->scope->canViewProject($user, $project);
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function update(User $user, LearningProject $project): bool
    {
        return $this->scope->canEditOwned($user, $project->created_by);
    }

    public function delete(User $user, LearningProject $project): bool
    {
        return $this->update($user, $project);
    }

    public function review(User $user, LearningProject $project): bool
    {
        return $this->scope->canReviewOwner($user, $project->creator);
    }

    public function manageActivity(User $user, LearningProject $project): bool
    {
        return $this->view($user, $project);
    }
}
