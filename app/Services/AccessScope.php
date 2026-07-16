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
            return $this->reviewableOwnerIds($user)
                ->merge($this->districtAliasIds($user))
                ->unique()
                ->values();
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

        $district = $user->parent;
        if (! $district || $district->role !== 'district_admin') {
            return $query->where('created_by', $user->id);
        }

        $libraryOwnerIds = $this->ownerIds($district);

        return $query->where(function (Builder $builder) use ($user, $libraryOwnerIds): void {
            $builder->where('created_by', $user->id)
                ->orWhere(function (Builder $library) use ($libraryOwnerIds): void {
                    $library->whereIn('created_by', $libraryOwnerIds)
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
            && $this->reviewableOwnerIds($reviewer)->contains((int) $owner->id);
    }

    public function reviewableOwnerIds(User $reviewer): Collection
    {
        if ($reviewer->role === 'super_admin') {
            return User::query()
                ->where('role', 'subdistrict_admin')
                ->pluck('id');
        }

        if ($reviewer->role !== 'district_admin') {
            return collect();
        }

        return User::query()
            ->where('role', 'subdistrict_admin')
            ->whereIn('parent_id', $this->districtAliasIds($reviewer))
            ->pluck('id');
    }

    private function districtAliasIds(User $district): Collection
    {
        $configuredSchoolIds = collect([
            config('sena.district_school_id'),
            config('sena.legacy_district_school_id'),
        ])->filter()->map(fn ($id): string => (string) $id);

        return User::query()
            ->where('role', 'district_admin')
            ->where(function (Builder $query) use ($district, $configuredSchoolIds): void {
                $query->where('id', $district->id)
                    ->orWhere('school_name', $district->school_name);

                if ($configuredSchoolIds->contains((string) $district->school_id)) {
                    $query->orWhereIn('school_id', $configuredSchoolIds);
                }
            })
            ->pluck('id')
            ->push($district->id)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }

    public function canEditOwned(User $user, int|string|null $ownerId): bool
    {
        return $user->role === 'super_admin' || (int) $user->id === (int) $ownerId;
    }

    public function canViewOwned(User $user, int|string|null $ownerId): bool
    {
        return $user->role === 'super_admin'
            || $this->ownerIds($user)->contains((int) $ownerId);
    }

    public function canViewCourse(User $user, Course $course): bool
    {
        if ($this->canEditOwned($user, $course->created_by)) {
            return true;
        }

        if ($this->canReviewOwner($user, $course->creator)) {
            return true;
        }

        if ($user->role !== 'subdistrict_admin' || $course->approval_status !== 'approved') {
            return false;
        }

        $district = $user->parent;

        return $district
            && $district->role === 'district_admin'
            && $this->ownerIds($district)->contains((int) $course->created_by);
    }

    public function canViewProject(User $user, LearningProject $project): bool
    {
        return $this->canEditOwned($user, $project->created_by)
            || $this->canReviewOwner($user, $project->creator);
    }
}
