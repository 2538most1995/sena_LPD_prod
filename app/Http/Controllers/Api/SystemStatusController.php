<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('SELECT 1');
            $database = 'connected';
            $status = 200;
        } catch (Throwable) {
            $database = 'unavailable';
            $status = 503;
        }

        return response()->json([
            'data' => [
                'application' => config('app.name'),
                'environment' => app()->environment(),
                'framework' => app()->version(),
                'database' => $database,
                'checked_at' => now()->toIso8601String(),
            ],
        ], $status);
    }
}
