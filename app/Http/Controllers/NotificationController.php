<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = (string) $request->user()->_id;

        $notifications = AppNotification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(30);

        // Mark all as read on page view
        AppNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, string $id)
    {
        $notification = AppNotification::findOrFail($id);
        abort_unless($notification->user_id === (string) $request->user()->_id, 403);

        $notification->markRead();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return back();
    }

    public function markAllRead(Request $request)
    {
        $userId = (string) $request->user()->_id;

        AppNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    public function feed(Request $request)
    {
        $userId = (string) $request->user()->_id;

        $notifications = AppNotification::where('user_id', $userId)
            ->orderByRaw(['is_read' => 1, 'created_at' => -1])
            ->limit(20)
            ->get(['_id', 'type', 'title', 'message', 'is_read', 'created_at'])
            ->map(fn($n) => [
                'id'         => (string) $n->_id,
                'type'       => $n->type,
                'title'      => $n->title,
                'message'    => $n->message,
                'is_read'    => (bool) $n->is_read,
                'created_at' => $n->created_at?->diffForHumans() ?? '',
            ]);

        $unread = AppNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unread,
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $notification = AppNotification::findOrFail($id);
        abort_unless($notification->user_id === (string) $request->user()->_id, 403);

        $notification->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return back();
    }
}
