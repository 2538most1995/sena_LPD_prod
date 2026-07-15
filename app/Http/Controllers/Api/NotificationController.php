<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->systemNotifications()
            ->latest('created_at')
            ->paginate(min(max($request->integer('per_page', 30), 1), 100)));
    }

    public function read(Request $request, SystemNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 404);
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'อ่านการแจ้งเตือนแล้ว']);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->systemNotifications()->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['message' => 'อ่านการแจ้งเตือนทั้งหมดแล้ว']);
    }
}
