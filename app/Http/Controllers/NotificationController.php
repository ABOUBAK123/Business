<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /** Page liste complète */
    public function index()
    {
        $notifications = Notification::where('recipient_id', Auth::id())
            ->latest('created_at')->paginate(30);
        return view('notifications.index', compact('notifications'));
    }

    /** AJAX — dernières 10 notifications pour le dropdown */
    public function ajaxList()
    {
        $items = Notification::where('recipient_id', Auth::id())
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'message'    => $n->message,
                'action_url' => $n->action_url,
                'is_read'    => $n->is_read,
                'time'       => $n->created_at?->diffForHumans(),
            ]);

        $unread = Notification::where('recipient_id', Auth::id())
            ->where('is_read', false)->count();

        return response()->json(['items' => $items, 'unread' => $unread]);
    }

    /** AJAX — marquer une notification comme lue */
    public function markRead(Notification $notification)
    {
        abort_if($notification->recipient_id !== Auth::id(), 403);
        $notification->update(['is_read' => true]);

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }
        return back();
    }

    /** AJAX / Form — marquer tout comme lu */
    public function markAllRead()
    {
        Notification::where('recipient_id', Auth::id())->update(['is_read' => true]);

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', 'Toutes les notifications marquées comme lues.');
    }

    /** AJAX — nombre non-lus (utilisé en polling) */
    public function unreadCount()
    {
        return response()->json([
            'count' => Notification::where('recipient_id', Auth::id())->where('is_read', false)->count(),
        ]);
    }
}
