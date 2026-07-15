<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Http\Requests\SaveCourseRequest;
use App\Models\Course;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\AuditService;
use App\Services\LegacyStorage;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourseController extends Controller
{
    public function __construct(
        private readonly AccessScope $scope,
        private readonly LegacyStorage $storage,
        private readonly NotificationService $notifications,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Course::class);
        /** @var User $user */
        $user = $request->user();
        $query = $this->scope->visibleCourses(Course::query(), $user)
            ->with('creator:id,display_name,school_name,parent_id,role')
            ->withCount('projects');

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('owner', 'like', "%{$search}%");
            });
        }
        if ($status = $request->query('approval_status')) {
            $query->where('approval_status', $status);
        }

        $courses = $query->latest('updated_at')
            ->paginate(min(max($request->integer('per_page', 18), 1), 100));
        $courses->getCollection()->transform(fn (Course $course): array => $this->present($course));

        return response()->json($courses);
    }

    public function store(SaveCourseRequest $request): JsonResponse
    {
        $this->authorize('create', Course::class);
        /** @var User $user */
        $user = $request->user();

        $course = DB::transaction(function () use ($request, $user): Course {
            $data = $request->safe()->except(['word_attachment', 'pdf_attachment']);
            $data['created_by'] = $user->id;
            $data['owner'] = $user->role === 'subdistrict_admin' ? $user->school_name : $data['owner'];
            $data['approval_status'] = $user->role === 'subdistrict_admin' ? 'pending' : 'approved';
            $data['submitted_at'] = $data['approval_status'] === 'pending' ? now() : null;
            $data['reviewed_at'] = $data['approval_status'] === 'approved' ? now() : null;

            if ($request->hasFile('word_attachment')) {
                $data['word_attachment_path'] = $this->storage->store($request->file('word_attachment'), 'courses');
                $data['word_attachment_name'] = mb_substr($request->file('word_attachment')->getClientOriginalName(), 0, 255);
            }
            if ($request->hasFile('pdf_attachment')) {
                $data['pdf_attachment_path'] = $this->storage->store($request->file('pdf_attachment'), 'courses');
                $data['pdf_attachment_name'] = mb_substr($request->file('pdf_attachment')->getClientOriginalName(), 0, 255);
            }

            $course = Course::query()->create($data);

            if ($course->approval_status === 'pending') {
                $this->notifications->send(
                    $user->parent,
                    'มีหลักสูตรรออนุมัติ',
                    $user->school_name.' ส่งหลักสูตร "'.$course->name.'" ให้พิจารณา',
                    '/approvals?course='.$course->id,
                );
            }

            $this->audit->record('course.created', $course, null, $course->toArray());

            return $course;
        });

        return response()->json(['message' => 'บันทึกหลักสูตรเรียบร้อย', 'data' => $this->present($course->load('creator'))], 201);
    }

    public function update(SaveCourseRequest $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);
        /** @var User $user */
        $user = $request->user();
        $before = $course->toArray();

        DB::transaction(function () use ($request, $course, $user, $before): void {
            $data = $request->safe()->except(['word_attachment', 'pdf_attachment']);
            $data['owner'] = $user->role === 'subdistrict_admin' ? $user->school_name : $data['owner'];
            $data['approval_status'] = $user->role === 'subdistrict_admin' ? 'pending' : 'approved';
            $data['reviewed_by'] = null;
            $data['review_note'] = null;
            $data['submitted_at'] = $data['approval_status'] === 'pending' ? now() : $course->submitted_at;
            $data['reviewed_at'] = $data['approval_status'] === 'approved' ? now() : null;

            if ($request->hasFile('word_attachment')) {
                $this->storage->delete($course->word_attachment_path);
                $data['word_attachment_path'] = $this->storage->store($request->file('word_attachment'), 'courses');
                $data['word_attachment_name'] = mb_substr($request->file('word_attachment')->getClientOriginalName(), 0, 255);
            }
            if ($request->hasFile('pdf_attachment')) {
                $this->storage->delete($course->pdf_attachment_path);
                $data['pdf_attachment_path'] = $this->storage->store($request->file('pdf_attachment'), 'courses');
                $data['pdf_attachment_name'] = mb_substr($request->file('pdf_attachment')->getClientOriginalName(), 0, 255);
            }

            $course->update($data);

            if ($course->approval_status === 'pending') {
                $this->notifications->send(
                    $user->parent,
                    'มีหลักสูตรส่งมาให้พิจารณาอีกครั้ง',
                    $user->school_name.' ส่งหลักสูตร "'.$course->name.'" ให้พิจารณา',
                    '/approvals?course='.$course->id,
                );
            }

            $this->audit->record('course.updated', $course, $before, $course->fresh()->toArray());
        });

        return response()->json(['message' => 'แก้ไขหลักสูตรเรียบร้อย', 'data' => $this->present($course->fresh()->load('creator'))]);
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->authorize('delete', $course);

        if ($course->projects()->exists()) {
            return response()->json(['message' => 'ไม่สามารถลบหลักสูตรที่ถูกนำไปจัดตั้งกลุ่มแล้ว'], 409);
        }

        $before = $course->toArray();
        DB::transaction(function () use ($course, $before): void {
            $this->storage->delete($course->word_attachment_path);
            $this->storage->delete($course->pdf_attachment_path);
            $this->audit->record('course.deleted', $course, $before, null);
            $course->delete();
        });

        return response()->json(['message' => 'ลบหลักสูตรเรียบร้อย']);
    }

    public function review(ReviewRequest $request, Course $course): JsonResponse
    {
        $this->authorize('review', $course);

        if ($course->approval_status !== 'pending') {
            return response()->json(['message' => 'หลักสูตรนี้ได้รับการพิจารณาแล้ว'], 409);
        }

        $before = $course->toArray();
        $status = $request->validated('status');
        DB::transaction(function () use ($request, $course, $status, $before): void {
            $course->update([
                'approval_status' => $status,
                'reviewed_by' => $request->user()->id,
                'review_note' => $request->validated('note'),
                'reviewed_at' => now(),
            ]);
            $request->user()->systemNotifications()
                ->where('is_read', false)
                ->where('link', 'like', '%course='.$course->id.'%')
                ->update(['is_read' => true]);

            $approved = $status === 'approved';
            $this->notifications->send(
                $course->creator,
                $approved ? 'หลักสูตรได้รับการอนุมัติ' : 'หลักสูตรถูกส่งกลับให้แก้ไข',
                'หลักสูตร "'.$course->name.'" '.($approved ? 'ได้รับการอนุมัติแล้ว' : 'ต้องแก้ไข: '.$request->validated('note')),
                '/courses',
            );
            $this->audit->record('course.reviewed', $course, $before, $course->fresh()->toArray());
        });

        return response()->json(['message' => $status === 'approved' ? 'อนุมัติหลักสูตรเรียบร้อย' : 'ส่งหลักสูตรกลับแก้ไขแล้ว']);
    }

    public function file(Request $request, Course $course, string $type): BinaryFileResponse|JsonResponse
    {
        $this->authorize('view', $course);
        if (! in_array($type, ['word', 'pdf'], true)) {
            abort(404);
        }

        $path = $course->{$type.'_attachment_path'};
        $name = $course->{$type.'_attachment_name'} ?: basename((string) $path);
        $absolute = $path ? $this->storage->absolute($path) : null;
        if (! $absolute) {
            return response()->json(['message' => 'ไม่พบไฟล์แนบ'], 404);
        }

        return response()->file($absolute, [
            'Content-Disposition' => 'inline; filename*=UTF-8\'\''.rawurlencode($name),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function present(Course $course): array
    {
        $data = $course->toArray();
        $data['attachments'] = array_map(function (array $attachment) use ($course): array {
            $attachment['url'] = route('api.courses.file', [$course, $attachment['type']]);

            return $attachment;
        }, $course->attachments());

        return $data;
    }
}
