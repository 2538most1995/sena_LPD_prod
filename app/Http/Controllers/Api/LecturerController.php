<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveLecturerRequest;
use App\Models\Lecturer;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LecturerController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Lecturer::query()->withCount('projects');
        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('id_card', 'like', "%{$search}%")
                    ->orWhere('expertise', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('first_name')->orderBy('last_name')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100)));
    }

    public function store(SaveLecturerRequest $request): JsonResponse
    {
        $lecturer = Lecturer::query()->create($request->validated());
        $this->audit->record('lecturer.created', $lecturer, null, $lecturer->toArray());

        return response()->json(['message' => 'เพิ่มวิทยากรเรียบร้อย', 'data' => $lecturer], 201);
    }

    public function update(SaveLecturerRequest $request, Lecturer $lecturer): JsonResponse
    {
        $before = $lecturer->toArray();
        $lecturer->update($request->validated());
        $this->audit->record('lecturer.updated', $lecturer, $before, $lecturer->fresh()->toArray());

        return response()->json(['message' => 'แก้ไขวิทยากรเรียบร้อย', 'data' => $lecturer->fresh()]);
    }

    public function destroy(Lecturer $lecturer): JsonResponse
    {
        if ($lecturer->projects()->exists()) {
            return response()->json(['message' => 'ไม่สามารถลบวิทยากรที่ถูกใช้ในกิจกรรม'], 409);
        }
        $before = $lecturer->toArray();
        $lecturer->delete();
        $this->audit->record('lecturer.deleted', $lecturer, $before, null);

        return response()->json(['message' => 'ลบวิทยากรเรียบร้อย']);
    }
}
