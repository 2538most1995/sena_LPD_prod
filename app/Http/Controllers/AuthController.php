<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'school_id' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! Auth::attempt([
            'school_id' => $credentials['school_id'],
            'password' => $credentials['password'],
            'status' => 'active',
        ], $request->boolean('remember'))) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'ข้อมูลเข้าสู่ระบบไม่ถูกต้อง',
                    'errors' => [
                        'school_id' => ['รหัสสถานศึกษาหรือรหัสผ่านไม่ถูกต้อง'],
                    ],
                ], 422);
            }

            throw ValidationException::withMessages([
                'school_id' => ['รหัสสถานศึกษาหรือรหัสผ่านไม่ถูกต้อง'],
            ]);
        }

        $request->session()->regenerate();

        return $request->expectsJson()
            ? response()->json(['data' => $request->user()?->only([
                'id', 'school_id', 'display_name', 'school_name', 'role',
            ])])
            : redirect()->intended(route('next.home'));
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(status: 204)
            : redirect()->route('next.home');
    }
}
