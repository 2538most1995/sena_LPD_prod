<?php

namespace App\Services;

use App\Models\Course;
use App\Models\LearningProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AccessScope
{
    public function ownerIds(User $user): Collection
    {
        if ($user->role === 'super_admin') {
            return User::query()->pluck('id');
        }

        if ($user->role === 'district_admin') {
            return $user->children()->pluck('id')->push($user->id)->unique()->values();
        }

        return collect([$user->id]);
    }

    public function owned(Builder $query, User $user): Builder
    {
        if ($user->role === 'super_admin') {
            return $query;
        }

        return $query->whereIn('created_by', $this->ownerIds($user));
    }

    public function visibleCourses(Builder $query, User $user): Builder
    {
        if ($user->role === 'super_admin') {
            return $query;
        }

        if ($user->role === 'district_admin') {
            return $query->whereIn('created_by', $this->ownerIds($user));
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder->where('created_by', $user->id)
                ->orWhere(function (Builder $parent) use ($user): void {
                    $parent->where('created_by', $user->parent_id)
                        ->where('approval_status', 'approved');
                });
        });
    }

    public function canReviewOwner(User $reviewer, ?User $owner): bool
    {
        if (! $owner) {
            return false;
        }

        if ($reviewer->role === 'super_admin') {
            return true;
        }

        return $reviewer->role === 'district_admin'
            && $owner->role === 'subdistrict_admin'
            && (int) $owner->parent_id === (int) $reviewer->id;
    }

    public function canEditOwned(User $user, int|string|null $ownerId): bool
    {
        return $user->role === 'super_admin' || (int) $user->id === (int) $ownerId;
    }

    public function canViewCourse(User $user, Course $course): bool
    {
        if ($this->canEditOwned($user, $course->created_by)) {
            return true;
        }

        if ($this->canReviewOwner($user, $course->creator)) {
            return true;
        }

        return $user->role === 'subdistrict_admin'
            && (int) $course->created_by === (int) $user->parent_id
            && $course->approval_status === 'approved';
    }

    public function canViewProject(User $user, LearningProject $project): bool
    {
        return $this->canEditOwned($user, $project->created_by)
            || $this->canReviewOwner($user, $project->creator);
    }
}
