<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use App\Services\AccessScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceDataController extends Controller
{
    public function __construct(private readonly AccessScope $scope) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['data' => [
            'courses' => $this->scope->visibleCourses(Course::query(), $user)
                ->where('approval_status', 'approved')->orderBy('name')->get(['id', 'name', 'hours', 'owner']),
            'lecturers' => $this->scope->owned(Lecturer::query(), $user)
                ->orderBy('first_name')->get(['id', 'created_by', 'prefix', 'first_name', 'last_name', 'expertise']),
            'students' => $this->scope->owned(Student::query(), $user)
                ->orderBy('first_name')->get(['id', 'created_by', 'prefix', 'first_name', 'last_name', 'id_card']),
            'district_admins' => $user->role === 'super_admin'
                ? User::query()->active()->where('role', 'district_admin')->orderBy('school_name')->get(['id', 'school_name'])
                : collect(),
        ]]);
    }
}
