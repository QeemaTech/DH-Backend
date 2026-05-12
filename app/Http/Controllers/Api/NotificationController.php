<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $perPage = (int) $request->integer('per_page', 15);
        $unreadOnly = filter_var($request->query('unread', false), FILTER_VALIDATE_BOOLEAN);

        $query = $unreadOnly
            ? $user->unreadNotifications()
            : $user->notifications();

        $notifications = $query->latest()->paginate(max(1, min(100, $perPage)));

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection(collect($notifications->items())),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_only' => $unreadOnly,
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();

        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'notification' => new NotificationResource($notification->fresh()),
            ],
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $unread = $user->unreadNotifications()->get();
        $count = $unread->count();
        $unread->markAsRead();

        return response()->json([
            'success' => true,
            'data' => [
                'marked_as_read' => $count,
                'unread_count' => 0,
            ],
        ]);
    }
}
