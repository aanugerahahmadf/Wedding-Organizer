<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            $notifications = $user->notifications()
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'status' => 'success',
                'data' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $notification = $user->notifications()->findOrFail($id);
            $notification->markAsRead();

            return response()->json([
                'status' => 'success',
                'message' => 'Notification marked as read',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark notification as read',
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->unreadNotifications->markAsRead();

            return response()->json([
                'status' => 'success',
                'message' => 'All notifications marked as read',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark all notifications as read',
            ], 500);
        }
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            return response()->json([
                'status' => 'success',
                'data' => [
                    'count' => $user->unreadNotifications()->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get unread count',
            ], 500);
        }
    }
}
