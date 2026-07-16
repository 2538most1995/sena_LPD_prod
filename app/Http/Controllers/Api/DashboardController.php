<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningProject;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use App\Services\AccessScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly AccessScope $scope) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $courses = $this->scope->visibleCourses(Course::query(), $user);
        $projects = $this->scope->owned(LearningProject::query(), $user);

        $data = [
            'totals' => [
                'courses' => (clone $courses)->count(),
                'projects' => (clone $projects)->count(),
                'students' => Student::query()->count(),
                'lecturers' => Lecturer::query()->count(),
                'subdistricts' => $user->role === 'super_admin'
                    ? User::query()->active()->where('role', 'subdistrict_admin')->count()
                    : $user->children()->active()->count(),
            ],
            'approvals' => [
                'courses_pending' => (clone $courses)->where('approval_status', 'pending')->count(),
                'courses_approved' => (clone $courses)->where('approval_status', 'approved')->count(),
                'projects_pending' => (clone $projects)->where('approval_status', 'pending')->count(),
                'projects_approved' => (clone $projects)->where('approval_status', 'approved')->count(),
            ],
            'projects_by_status' => (clone $projects)->selectRaw('approval_status, COUNT(*) total')->groupBy('approval_status')->pluck('total', 'approval_status'),
            'upcoming' => (clone $projects)->with('course:id,name,hours')
                ->whereDate('end_date', '>=', today())->orderBy('start_date')->limit(5)->get(),
            'recent_courses' => (clone $courses)->with('creator:id,school_name')->latest('updated_at')->limit(5)->get(),
            'notifications_unread' => $user->systemNotifications()->where('is_read', false)->count(),
        ];

        return response()->json(['data' => $data]);
    }
}
