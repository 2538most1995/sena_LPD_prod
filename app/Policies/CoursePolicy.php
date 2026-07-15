<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use App\Services\AccessScope;

class CoursePolicy
{
    public function __construct(private readonly AccessScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->status === 'active';
    }

    public function view(User $user, Course $course): bool
    {
        return $this->scope->canViewCourse($user, $course);
    }

    public function create(User $user): bool
    {
        return $user->status === 'active';
    }

    public function update(User $user, Course $course): bool
    {
        return $this->scope->canEditOwned($user, $course->created_by);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->update($user, $course);
    }

    public function review(User $user, Course $course): bool
    {
        return $this->scope->canReviewOwner($user, $course->creator);
    }
}
