<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Notification::query()
            ->where('user_id', $request->user()->id)
            ->with(['vendor', 'vendorDocument.documentType'])
            ->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->boolean('unread')) {
            $query->where('is_read', false);
        }

        $notifications = $query->paginate(20)->withQueryString();
        $unreadCount = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function read(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->markAsRead();

        if ($notification->action_url) {
            return redirect()->to($notification->action_url);
        }

        return back()->with('status', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }
}
