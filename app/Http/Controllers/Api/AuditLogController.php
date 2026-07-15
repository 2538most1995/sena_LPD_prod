<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->role, ['super_admin', 'district_admin'], true), 403);
        $query = AuditLog::query()->with('user:id,display_name,school_name,role');
        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }
        if ($request->user()->role === 'district_admin') {
            $ids = $request->user()->children()->pluck('id')->push($request->user()->id);
            $query->whereIn('user_id', $ids);
        }

        return response()->json($query->latest('created_at')->paginate(50));
    }
}
