<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * List user notifications
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'all');
        $user = $request->user();

        $notifications = $this->notificationService->getUserNotifications($user, $status);
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'status' => 'success',
            'message' => 'Notifications retrieved successfully.',
            'data' => [
                'unreadCount' => $unreadCount,
                'notifications' => NotificationResource::collection($notifications)->response()->getData(true)
            ]
        ]);
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $this->notificationService->markNotificationAsRead($request->user(), $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.',
            'data' => null
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllUserNotificationsAsRead($request->user());

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read.',
            'data' => null
        ]);
    }
}
