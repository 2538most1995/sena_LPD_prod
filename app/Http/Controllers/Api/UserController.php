<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveUserRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\LegacyStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        private readonly LegacyStorage $storage,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        /** @var User $actor */
        $actor = $request->user();
        $query = User::query()->with('parent:id,school_name');
        if ($actor->role === 'district_admin') {
            $query->where(fn ($builder) => $builder->where('id', $actor->id)->orWhere('parent_id', $actor->id));
        }
        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('school_id', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('school_name', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderByRaw("FIELD(role, 'super_admin', 'district_admin', 'subdistrict_admin')")
            ->orderBy('school_name')->paginate(min(max($request->integer('per_page', 30), 1), 100)));
    }

    public function store(SaveUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);
        /** @var User $actor */
        $actor = $request->user();
        $data = $this->accountData($request, $actor);
        $data['password_hash'] = Hash::make($request->validated('password'));
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->storage->store($request->file('photo'), 'profiles');
        }
        $user = User::query()->create($data);
        $this->audit->record('user.created', $user, null, $user->toArray());

        return response()->json(['message' => 'เพิ่มบัญชีผู้ดูแลเรียบร้อย', 'data' => $user], 201);
    }

    public function update(SaveUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        /** @var User $actor */
        $actor = $request->user();
        $before = $user->toArray();
        $data = $this->accountData($request, $actor);
        if ($request->filled('password')) {
            $data['password_hash'] = Hash::make($request->validated('password'));
        }
        if ($request->hasFile('photo')) {
            $this->storage->delete($user->photo_path);
            $data['photo_path'] = $this->storage->store($request->file('photo'), 'profiles');
        }
        $user->update($data);
        $this->audit->record('user.updated', $user, $before, $user->fresh()->toArray());

        return response()->json(['message' => 'แก้ไขบัญชีเรียบร้อย', 'data' => $user->fresh()]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $before = $user->toArray();
        $user->update(['status' => 'inactive']);
        $this->audit->record('user.deactivated', $user, $before, $user->fresh()->toArray());

        return response()->json(['message' => 'ระงับบัญชีเรียบร้อย']);
    }

    private function accountData(SaveUserRequest $request, User $actor): array
    {
        $data = $request->safe()->except(['password', 'photo']);
        if ($actor->role === 'district_admin') {
            $data['role'] = 'subdistrict_admin';
            $data['parent_id'] = $actor->id;
            $data['status'] = $data['status'] ?? 'active';
        } elseif ($data['role'] === 'subdistrict_admin' && empty($data['parent_id'])) {
            abort(422, 'กรุณาระบุ Admin ระดับอำเภอที่สังกัด');
        } elseif ($data['role'] !== 'subdistrict_admin') {
            $data['parent_id'] = null;
        }

        return $data;
    }
}
