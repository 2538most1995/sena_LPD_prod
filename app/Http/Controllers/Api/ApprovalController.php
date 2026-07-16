<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningProject;
use App\Models\User;
use App\Services\AccessScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(private readonly AccessScope $scope) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless(in_array($user->role, ['super_admin', 'district_admin'], true), 403);

        $courses = $this->courseScope(Course::query(), $user)
            ->with('creator:id,display_name,school_name,parent_id')
            ->latest('submitted_at');
        $projects = $this->projectScope(LearningProject::query(), $user)
            ->with([
                'creator:id,display_name,school_name,parent_id',
                'course:id,name,hours',
                'lecturer:id,prefix,first_name,last_name',
            ])
            ->latest('submitted_at');

        return response()->json(['data' => [
            'pending' => [
                'courses' => (clone $courses)->where('approval_status', 'pending')->get(),
                'projects' => (clone $projects)->where('approval_status', 'pending')->get()->map(function (LearningProject $project): array {
                    $data = $project->toArray();
                    $data['total_budget'] = collect([
                        'lecturer_cost', 'material_cost', 'board_cost', 'food_cost',
                        'snack_cost', 'place_cost', 'transport_cost', 'other_cost',
                    ])->sum(fn (string $field): float => (float) $project->{$field});

                    return $data;
                }),
            ],
            'history' => [
                'courses' => (clone $courses)->whereIn('approval_status', ['approved', 'revision'])
                    ->whereNotNull('reviewed_at')->latest('reviewed_at')->limit(100)->get(),
                'projects' => (clone $projects)->whereIn('approval_status', ['approved', 'revision'])
                    ->whereNotNull('reviewed_at')->latest('reviewed_at')->limit(100)->get(),
            ],
        ]]);
    }

    private function courseScope(Builder $query, User $user): Builder
    {
        return $query->whereIn('created_by', $this->scope->reviewableOwnerIds($user));
    }

    private function projectScope(Builder $query, User $user): Builder
    {
        return $query->whereIn('created_by', $this->scope->reviewableOwnerIds($user));
    }
}
