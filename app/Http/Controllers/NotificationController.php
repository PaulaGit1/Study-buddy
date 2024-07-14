<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserNotification;

class NotificationController extends Controller
{
    public function index()
    {
        // Mark all unread notifications as read
        UserNotification::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);

        // Get all notifications
        $notifications = UserNotification::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();

        return view('notifications', compact('notifications'));
    }
}
