<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Show every in-app notification belonging to the signed in user.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('notifications.index', [
            'layout' => $user->isCustomer() ? 'layouts.customer' : 'layouts.admin',
            'notifications' => $user->inAppNotifications()->latestFirst()->paginate(15),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()?->getKey(), 403);

        $notification->markAsRead();

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Mark every notification belonging to the signed in user as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->inAppNotifications()->unread()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
