<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get unread notifications for the authenticated user
     */
    public function getUnread(Request $request)
    {
        $user = $request->user();

        $unreadNotifications = $user->notifications()->latest()->take(15)->get();
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'notifications' => $unreadNotifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark a specific notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'Đã đánh dấu là đã đọc.']);
        }

        return response()->json(['message' => 'Không tìm thấy thông báo.'], 404);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'Đã đánh dấu tất cả là đã đọc.']);
    }
}
