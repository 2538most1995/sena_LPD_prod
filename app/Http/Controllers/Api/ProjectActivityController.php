<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityPhoto;
use App\Models\LearningProject;
use App\Services\AuditService;
use App\Services\LegacyStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectActivityController extends Controller
{
    public function __construct(
        private readonly LegacyStorage $storage,
        private readonly AuditService $audit,
    ) {}

    public function participants(Request $request, LearningProject $project): JsonResponse
    {
        $this->authorize('manageActivity', $project);
        $data = $request->validate([
            'student_ids' => ['present', 'array'],
            'student_ids.*' => ['integer', 'distinct', 'exists:students,id'],
        ]);
        $before = $project->students()->pluck('students.id')->all();
        $project->students()->sync($data['student_ids']);
        $this->audit->record('project.participants.updated', $project, ['student_ids' => $before], $data);

        return response()->json(['message' => 'บันทึกรายชื่อผู้เรียนเรียบร้อย']);
    }

    public function scores(Request $request, LearningProject $project): JsonResponse
    {
        $this->authorize('manageActivity', $project);
        $data = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'scores.*.knowledge' => ['required', 'numeric', 'between:0,20'],
            'scores.*.skill' => ['required', 'numeric', 'between:0,40'],
            'scores.*.attribute' => ['required', 'numeric', 'between:0,40'],
        ]);
        $participantIds = $project->students()->pluck('students.id');
        foreach ($data['scores'] as $score) {
            abort_unless($participantIds->contains((int) $score['student_id']), 422, 'พบผู้เรียนที่ไม่ได้อยู่ในกิจกรรม');
            DB::table('scores')->updateOrInsert([
                'project_id' => $project->id,
                'student_id' => $score['student_id'],
            ], [
                'knowledge_score' => $score['knowledge'],
                'skill_score' => $score['skill'],
                'attribute_score' => $score['attribute'],
                'updated_at' => now(),
            ]);
        }
        $this->audit->record('project.scores.updated', $project, null, ['count' => count($data['scores'])]);

        return response()->json(['message' => 'บันทึกคะแนนเรียบร้อย']);
    }

    public function uploadPhotos(Request $request, LearningProject $project): JsonResponse
    {
        $this->authorize('manageActivity', $project);
        $data = $request->validate([
            'photo_type' => ['required', Rule::in(['material', 'activity'])],
            'photos' => ['required', 'array', 'min:1', 'max:4'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'captions' => ['nullable', 'array', 'max:4'],
            'captions.*' => ['nullable', 'string', 'max:255'],
        ]);
        $start = (int) $project->photos()->where('photo_type', $data['photo_type'])->max('sort_order');
        $created = [];
        foreach ($request->file('photos') as $index => $file) {
            $path = $this->storage->store($file, $data['photo_type'] === 'material' ? 'materials' : 'activities');
            $created[] = $project->photos()->create([
                'photo_type' => $data['photo_type'],
                'file_path' => $path,
                'caption' => $data['captions'][$index] ?? null,
                'sort_order' => $start + $index + 1,
            ]);
        }
        $this->audit->record('project.photos.uploaded', $project, null, ['count' => count($created), 'type' => $data['photo_type']]);

        return response()->json(['message' => 'อัปโหลดภาพเรียบร้อย', 'data' => $created], 201);
    }

    public function deletePhoto(LearningProject $project, ActivityPhoto $photo): JsonResponse
    {
        $this->authorize('manageActivity', $project);
        abort_unless((int) $photo->project_id === (int) $project->id, 404);
        $before = $photo->toArray();
        $this->storage->delete($photo->file_path);
        $photo->delete();
        $this->audit->record('project.photo.deleted', $project, $before, null);

        return response()->json(['message' => 'ลบภาพเรียบร้อย']);
    }

    public function photoFile(LearningProject $project, ActivityPhoto $photo): BinaryFileResponse
    {
        $this->authorize('view', $project);
        abort_unless((int) $photo->project_id === (int) $project->id, 404);
        $absolute = $this->storage->absolute($photo->file_path);
        abort_unless($absolute, 404);

        return response()->file($absolute, ['X-Content-Type-Options' => 'nosniff']);
    }
}
