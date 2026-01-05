<?php

namespace App\Http\Controllers;

use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification Controller
 *
 * Handles user notification operations including
 * fetching, marking as read, and preferences.
 */
class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user notifications.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = min($request->input('limit', 20), 100);
        $unreadOnly = $request->boolean('unread_only', false);

        $result = $this->notificationService->getUserNotifications($user, $limit, $unreadOnly);

        return response()->json([
            'success' => true,
            'data' => $result['notifications'],
            'unread_count' => $result['unread_count'],
        ]);
    }

    /**
     * Get unread count.
     *
     * @return JsonResponse
     */
    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark notification as read.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function markAsRead(string $id): JsonResponse
    {
        $user = auth()->user();
        $success = $this->notificationService->markAsRead($user, $id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications as read.
     *
     * @return JsonResponse
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = auth()->user();
        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'message' => "{$count} notifications marked as read.",
            'marked_count' => $count,
        ]);
    }

    /**
     * Delete a notification.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $user = auth()->user();
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted.',
        ]);
    }

    /**
     * Clear all notifications.
     *
     * @return JsonResponse
     */
    public function clearAll(): JsonResponse
    {
        $user = auth()->user();
        $count = $user->notifications()->count();
        $user->notifications()->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} notifications cleared.",
            'cleared_count' => $count,
        ]);
    }
}
