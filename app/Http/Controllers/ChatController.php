<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    private function chatEnabled(): bool
    {
        $s = AppSetting::where('key', 'chat_enabled')->first();
        return $s ? (bool)$s->value : true;
    }

    private function chatScope(): string
    {
        $s = AppSetting::where('key', 'chat_scope')->first();
        return $s ? $s->value : 'all';
    }

    public function index()
    {
        abort_unless($this->chatEnabled(), 403, 'Le chat est désactivé par l\'administrateur.');
        return view('chat.index', [
            'chatScope' => $this->chatScope(),
        ]);
    }

    /** Liste des utilisateurs disponibles pour le chat direct */
    public function users(Request $request)
    {
        $me = Auth::user();
        $scope = $this->chatScope();

        $q = User::where('id', '!=', $me->id)->orderBy('name');

        if ($scope === 'same_admin') {
            // Même administration uniquement
            $q->where('issuing_administration_id', $me->issuing_administration_id);
        }

        $users = $q->select('id', 'name', 'role', 'issuing_administration_id')->get()
            ->map(fn($u) => [
                'id'       => $u->id,
                'name'     => $u->name,
                'initials' => strtoupper(substr($u->name, 0, 2)),
                'role'     => $u->role ?? 'user',
            ]);

        return response()->json($users);
    }

    /** Messages d'un salon ou d'une conversation directe */
    public function messages(Request $request)
    {
        abort_unless($this->chatEnabled(), 403);
        $me = Auth::user();

        $room  = $request->get('room', 'general');
        $since = $request->get('since'); // timestamp pour polling

        $q = ChatMessage::where('room', $room)
            ->orderBy('created_at', 'asc');

        if ($since) {
            $q->where('created_at', '>', $since);
        } else {
            $q->latest('created_at')->take(60)->reorder('created_at', 'asc');
        }

        $messages = $q->get()->map(fn($m) => [
            'id'       => $m->id,
            'sender_id'=> $m->sender_id,
            'name'     => $m->sender_name,
            'initials' => $m->sender_initials,
            'text'     => $m->text,
            'room'     => $m->room,
            'type'     => $m->type,
            'time'     => $m->created_at?->format('H:i'),
            'ts'       => $m->created_at?->toISOString(),
            'mine'     => $m->sender_id === $me->id,
        ]);

        return response()->json($messages);
    }

    /** Envoyer un message */
    public function send(Request $request)
    {
        abort_unless($this->chatEnabled(), 403);

        $request->validate([
            'text'         => 'required|string|max:2000',
            'room'         => 'nullable|string|max:255',
            'recipient_id' => 'nullable|string|max:128',
        ]);

        $user = Auth::user();
        $room = $request->get('room', 'general');
        $type = $request->get('recipient_id') ? 'direct' : 'group';

        $message = ChatMessage::create([
            'id'              => Str::uuid(),
            'sender_id'       => $user->id,
            'sender_name'     => $user->name,
            'sender_initials' => strtoupper(substr($user->name, 0, 2)),
            'recipient_id'    => $request->get('recipient_id'),
            'text'            => $request->text,
            'room'            => $room,
            'type'            => $type,
        ]);

        // Notifier le destinataire d'un message direct
        NotificationService::chatMessageReceived($message, $user->name);

        return response()->json([
            'id'       => $message->id,
            'sender_id'=> $message->sender_id,
            'name'     => $message->sender_name,
            'initials' => $message->sender_initials,
            'text'     => $message->text,
            'room'     => $message->room,
            'type'     => $message->type,
            'time'     => now()->format('H:i'),
            'ts'       => now()->toISOString(),
            'mine'     => true,
        ], 201);
    }
}

