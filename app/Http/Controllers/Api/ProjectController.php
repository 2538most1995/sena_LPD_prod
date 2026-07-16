<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Http\Requests\SaveProjectRequest;
use App\Models\Course;
use App\Models\LearningProject;
use App\Models\Lecturer;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    private const COST_FIELDS = [
        'lecturer_cost', 'material_cost', 'board_cost', 'food_cost',
        'snack_cost', 'place_cost', 'transport_cost', 'other_cost',
    ];

    public function __construct(
        private readonly AccessScope $scope,
        private readonly NotificationService $notifications,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LearningProject::class);
        /** @var User $user */
        $user = $request->user();
        $query = $this->scope->owned(LearningProject::query(), $user)
            ->with([
                'course:id,name,hours,approval_status',
                'lecturer:id,prefix,first_name,last_name,expertise',
                'creator:id,display_name,school_name,parent_id,role',
            ])
            ->withCount('students');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('place', 'like', "%{$search}%")
                    ->orWhereHas('course', fn ($course) => $course->where('name', 'like', "%{$search}%"));
            });
        }
        if ($status = $request->query('approval_status')) {
            $query->where('approval_status', $status);
        }
        if ($year = $request->integer('fiscal_year')) {
            $query->where('fiscal_year', $year);
        }

        $projects = $query->latest('updated_at')
            ->paginate(min(max($request->integer('per_page', 18), 1), 100));
        $projects->getCollection()->transform(fn (LearningProject $project): array => $this->present($project));

        return response()->json($projects);
    }

    public function show(LearningProject $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project->load([
            'course', 'lecturer', 'creator:id,display_name,school_name,parent_id,role',
            'students' => fn ($query) => $query->orderBy('first_name')->orderBy('last_name'),
            'photos' => fn ($query) => $query->orderBy('photo_type')->orderBy('sort_order'),
        ]);

        $scores = DB::table('scores')->where('project_id', $project->id)->get()->keyBy('student_id');
        $data = $this->present($project);
        $data['students'] = $project->students->map(function ($student) use ($scores): array {
            $score = $scores->get($student->id);

            return array_merge($student->toArray(), [
                'score_recorded' => $score !== null,
                'scores' => [
                    'knowledge' => (float) ($score->knowledge_score ?? 0),
                    'skill' => (float) ($score->skill_score ?? 0),
                    'attribute' => (float) ($score->attribute_score ?? 0),
                ],
            ]);
        });
        $data['photos'] = $project->photos->map(function ($photo) use ($project): array {
            return array_merge($photo->toArray(), [
                'url' => route('api.projects.photos.file', [$project, $photo]),
            ]);
        });

        return response()->json(['data' => $data]);
    }

    public function store(SaveProjectRequest $request): JsonResponse
    {
        $this->authorize('create', LearningProject::class);
        /** @var User $user */
        $user = $request->user();
        $course = Course::query()->findOrFail($request->integer('course_id'));
        $this->authorize('view', $course);
        $lecturer = Lecturer::query()->findOrFail($request->integer('lecturer_id'));
        $this->authorize('view', $lecturer);
        if ($course->approval_status !== 'approved') {
            return response()->json(['message' => 'หลักสูตรนี้ยังไม่ได้รับอนุมัติ'], 422);
        }

        $project = DB::transaction(function () use ($request, $user, $course): LearningProject {
            $data = $this->projectData($request, $course);
            $data['created_by'] = $user->id;
            $data['approval_status'] = $user->role === 'subdistrict_admin' ? 'pending' : 'approved';
            $data['submitted_at'] = $data['approval_status'] === 'pending' ? now() : null;
            $data['reviewed_at'] = $data['approval_status'] === 'approved' ? now() : null;
            $project = LearningProject::query()->create($data);

            if ($project->approval_status === 'pending') {
                $this->notifications->send(
                    $user->parent,
                    'มีคำขอจัดตั้งกลุ่มรออนุมัติ',
                    $user->school_name.' ส่งคำขอ "'.$project->title.'" ให้พิจารณา',
                    '/approvals?project='.$project->id,
                );
            }
            $this->audit->record('project.created', $project, null, $project->toArray());

            return $project;
        });

        return response()->json(['message' => 'บันทึกการจัดตั้งกลุ่มเรียบร้อย', 'data' => $this->present($project->load(['course', 'lecturer', 'creator']))], 201);
    }

    public function update(SaveProjectRequest $request, LearningProject $project): JsonResponse
    {
        $this->authorize('update', $project);
        /** @var User $user */
        $user = $request->user();
        $course = Course::query()->findOrFail($request->integer('course_id'));
        $this->authorize('view', $course);
        $lecturer = Lecturer::query()->findOrFail($request->integer('lecturer_id'));
        $this->authorize('view', $lecturer);
        if ($course->approval_status !== 'approved') {
            return response()->json(['message' => 'หลักสูตรนี้ยังไม่ได้รับอนุมัติ'], 422);
        }

        $before = $project->toArray();
        DB::transaction(function () use ($request, $project, $user, $course, $before): void {
            $data = $this->projectData($request, $course);
            $data['approval_status'] = $user->role === 'subdistrict_admin' ? 'pending' : 'approved';
            $data['reviewed_by'] = null;
            $data['review_note'] = null;
            $data['submitted_at'] = $data['approval_status'] === 'pending' ? now() : $project->submitted_at;
            $data['reviewed_at'] = $data['approval_status'] === 'approved' ? now() : null;
            $project->update($data);

            if ($project->approval_status === 'pending') {
                $this->notifications->send(
                    $user->parent,
                    'มีคำขอจัดตั้งกลุ่มส่งมาอีกครั้ง',
                    $user->school_name.' ส่งคำขอ "'.$project->title.'" ให้พิจารณา',
                    '/approvals?project='.$project->id,
                );
            }
            $this->audit->record('project.updated', $project, $before, $project->fresh()->toArray());
        });

        return response()->json(['message' => 'แก้ไขการจัดตั้งกลุ่มเรียบร้อย', 'data' => $this->present($project->fresh()->load(['course', 'lecturer', 'creator']))]);
    }

    public function destroy(LearningProject $project): JsonResponse
    {
        $this->authorize('delete', $project);
        $before = $project->toArray();
        DB::transaction(function () use ($project, $before): void {
            $this->audit->record('project.deleted', $project, $before, null);
            $project->delete();
        });

        return response()->json(['message' => 'ลบการจัดตั้งกลุ่มเรียบร้อย']);
    }

    public function review(ReviewRequest $request, LearningProject $project): JsonResponse
    {
        $this->authorize('review', $project);
        if ($project->approval_status !== 'pending') {
            return response()->json(['message' => 'คำขอนี้ได้รับการพิจารณาแล้ว'], 409);
        }

        $before = $project->toArray();
        $status = $request->validated('status');
        DB::transaction(function () use ($request, $project, $status, $before): void {
            $project->update([
                'approval_status' => $status,
                'reviewed_by' => $request->user()->id,
                'review_note' => $request->validated('note'),
                'reviewed_at' => now(),
            ]);
            $request->user()->systemNotifications()
                ->where('is_read', false)
                ->where('link', 'like', '%project='.$project->id.'%')
                ->update(['is_read' => true]);

            $approved = $status === 'approved';
            $this->notifications->send(
                $project->creator,
                $approved ? 'อนุมัติการจัดตั้งกลุ่มแล้ว' : 'ส่งคำขอจัดตั้งกลุ่มกลับแก้ไข',
                'คำขอ "'.$project->title.'" '.($approved ? 'ได้รับการอนุมัติแล้ว' : 'ต้องแก้ไข: '.$request->validated('note')),
                '/projects',
            );
            $this->audit->record('project.reviewed', $project, $before, $project->fresh()->toArray());
        });

        return response()->json(['message' => $status === 'approved' ? 'อนุมัติการจัดตั้งกลุ่มเรียบร้อย' : 'ส่งคำขอกลับแก้ไขแล้ว']);
    }

    private function projectData(SaveProjectRequest $request, Course $course): array
    {
        $data = $request->validated();
        $data['format_type'] = $course->hours >= 10 ? 'หลักสูตร 10 ชั่วโมงขึ้นไป' : 'หลักสูตร 3-9 ชั่วโมง';
        foreach (self::COST_FIELDS as $field) {
            $data[$field] = (float) ($data[$field] ?? 0);
        }

        return $data;
    }

    private function present(LearningProject $project): array
    {
        $data = $project->toArray();
        $data['total_budget'] = collect(self::COST_FIELDS)->sum(fn (string $field): float => (float) $project->{$field});

        return $data;
    }
}
