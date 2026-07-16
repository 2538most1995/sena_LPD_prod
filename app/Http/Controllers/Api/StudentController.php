<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveStudentRequest;
use App\Models\Student;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(
        private readonly AccessScope $scope,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);
        /** @var User $user */
        $user = $request->user();
        $query = $this->scope->owned(Student::query(), $user)
            ->with('creator:id,school_name')
            ->withCount('projects');
        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('id_card', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('first_name')->orderBy('last_name')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100)));
    }

    public function store(SaveStudentRequest $request): JsonResponse
    {
        $this->authorize('create', Student::class);
        $student = Student::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);
        $this->audit->record('student.created', $student, null, $student->toArray());

        return response()->json(['message' => 'เพิ่มผู้เรียนเรียบร้อย', 'data' => $student], 201);
    }

    public function update(SaveStudentRequest $request, Student $student): JsonResponse
    {
        $this->authorize('update', $student);
        $before = $student->toArray();
        $student->update($request->validated());
        $this->audit->record('student.updated', $student, $before, $student->fresh()->toArray());

        return response()->json(['message' => 'แก้ไขผู้เรียนเรียบร้อย', 'data' => $student->fresh()]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $this->authorize('delete', $student);
        if ($student->projects()->exists()) {
            return response()->json(['message' => 'ไม่สามารถลบผู้เรียนที่มีประวัติเข้าร่วมกิจกรรม'], 409);
        }
        $before = $student->toArray();
        try {
            $student->delete();
        } catch (QueryException) {
            return response()->json(['message' => 'ไม่สามารถลบผู้เรียนที่ถูกใช้งานอยู่'], 409);
        }
        $this->audit->record('student.deleted', $student, $before, null);

        return response()->json(['message' => 'ลบผู้เรียนเรียบร้อย']);
    }
}
