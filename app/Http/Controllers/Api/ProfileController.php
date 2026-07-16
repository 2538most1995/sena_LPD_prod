<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\LegacyStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Throwable;

class ProfileController extends Controller
{
    public function __construct(
        private readonly LegacyStorage $storage,
        private readonly AuditService $audit,
    ) {}

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:150'],
            'school_name' => ['required', 'string', 'max:255'],
            'teacher_name' => ['required', 'string', 'max:200'],
            'position' => ['nullable', 'string', 'max:150'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'subdistrict' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:30'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $before = $user->toArray();
        if (! empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
        }
        unset($data['password']);
        $oldPhotoPath = $user->photo_path;
        $newPhotoPath = null;
        if ($request->hasFile('photo')) {
            $newPhotoPath = $this->storage->store($request->file('photo'), 'profiles');
            $data['photo_path'] = $newPhotoPath;
        }

        try {
            $user->update($data);
            $this->audit->record('profile.updated', $user, $before, $user->fresh()->toArray());
        } catch (Throwable $exception) {
            $this->storage->delete($newPhotoPath);
            throw $exception;
        }

        if ($newPhotoPath && $oldPhotoPath !== $newPhotoPath) {
            $this->storage->delete($oldPhotoPath);
        }

        $freshUser = $user->fresh();
        $responseData = $freshUser->toArray();
        $responseData['photo_url'] = $freshUser->photo_path
            ? route('api.profile.photo').'?v='.rawurlencode((string) $freshUser->updated_at?->timestamp)
            : null;

        return response()->json(['message' => 'บันทึกโปรไฟล์เรียบร้อย', 'data' => $responseData]);
    }

    public function photo(Request $request): mixed
    {
        /** @var User $user */
        $user = $request->user();
        $absolute = $user->photo_path ? $this->storage->absolute($user->photo_path) : null;
        abort_unless($absolute, 404);

        $response = response()->file($absolute);
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
