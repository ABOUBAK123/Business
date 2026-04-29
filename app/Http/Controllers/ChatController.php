<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\AppSetting;
use App\Models\IssuingAdministration;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    private const ONLINE_TTL_SECONDS = 300;

    private function onlineKey(string $userId): string
    {
        return 'chat:online:' . $userId;
    }

    private function touchOnline(string $userId): void
    {
        Cache::put($this->onlineKey($userId), true, now()->addSeconds(self::ONLINE_TTL_SECONDS));
    }

    private function isUserOnline(string $userId): bool
    {
        return Cache::has($this->onlineKey($userId));
    }

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

    private function usersHasIssuingAdministrationColumn(): bool
    {
        return Schema::hasColumn('users', 'issuing_administration_id');
    }

    private function applyUsersScope($query, User $me, string $scope)
    {
        if ($scope === 'same_admin' && $this->usersHasIssuingAdministrationColumn() && !empty($me->issuing_administration_id)) {
            $query->where('issuing_administration_id', $me->issuing_administration_id);
        }

        return $query;
    }

    private function resolveAdministrationNames(array $administrationIds): array
    {
        $ids = array_values(array_filter(array_unique($administrationIds)));
        if (empty($ids)) {
            return [];
        }

        return IssuingAdministration::whereIn('id', $ids)
            ->pluck('name', 'id')
            ->toArray();
    }

    public function index()
    {
        abort_unless($this->chatEnabled(), 403, 'Le chat est désactivé par l\'administrateur.');
        $me = Auth::user();
        if ($me) {
            $this->touchOnline((string) $me->id);
        }
        return view('chat.index', [
            'chatScope' => $this->chatScope(),
        ]);
    }

    /** Liste des utilisateurs disponibles pour le chat direct */
    public function users(Request $request)
    {
        $me = Auth::user();
        $this->touchOnline((string) $me->id);
        $scope = $this->chatScope();

        $q = User::where('id', '!=', $me->id)
            ->orderBy('name');

        $q = $this->applyUsersScope($q, $me, $scope);

        $select = ['id', 'name', 'role'];
        if ($this->usersHasIssuingAdministrationColumn()) {
            $select[] = 'issuing_administration_id';
        }

        $usersRaw = $q->select($select)->get()
            ->filter(fn ($u) => $this->isUserOnline((string) $u->id));

        $adminNameById = $this->resolveAdministrationNames($usersRaw
            ->pluck('issuing_administration_id')
            ->filter()
            ->all());

        $users = $usersRaw->map(fn ($u) => [
                'id'       => $u->id,
                'name'     => $u->name,
                'initials' => strtoupper(substr($u->name, 0, 2)),
                'role'     => $u->role ?? 'user',
                'online'   => true,
                'administration_id' => $u->issuing_administration_id ?? null,
                'administration_name' => $adminNameById[$u->issuing_administration_id] ?? 'Sans administration',
            ]);

        return response()->json($users);
    }

    /** Liste des utilisateurs en ligne groupés par administration */
    public function onlineByAdministration(Request $request)
    {
        $me = Auth::user();
        $this->touchOnline((string) $me->id);
        $scope = $this->chatScope();

        $q = User::query()->orderBy('name');
        $q = $this->applyUsersScope($q, $me, $scope);

        $select = ['id', 'name', 'role'];
        if ($this->usersHasIssuingAdministrationColumn()) {
            $select[] = 'issuing_administration_id';
        }

        $onlineRaw = $q->select($select)->get()
            ->filter(fn ($u) => $this->isUserOnline((string) $u->id));

        $adminNameById = $this->resolveAdministrationNames($onlineRaw
            ->pluck('issuing_administration_id')
            ->filter()
            ->all());

        $grouped = $onlineRaw
            ->map(function ($u) use ($adminNameById, $me) {
                $adminId = $u->issuing_administration_id ?? null;
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'initials' => strtoupper(substr($u->name, 0, 2)),
                    'role' => $u->role ?? 'user',
                    'is_me' => (string) $u->id === (string) $me->id,
                    'administration_id' => $adminId,
                    'administration_name' => $adminNameById[$adminId] ?? 'Sans administration',
                ];
            })
            ->groupBy(fn ($u) => ($u['administration_id'] ?? 'none') . '|' . $u['administration_name'])
            ->map(function ($users, $groupKey) {
                [$administrationId, $administrationName] = explode('|', $groupKey, 2);
                return [
                    'administration_id' => $administrationId === 'none' ? null : $administrationId,
                    'administration_name' => $administrationName,
                    'count' => $users->count(),
                    'users' => $users->values(),
                ];
            })
            ->sortBy('administration_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json($grouped);
    }

    /** Messages d'un salon ou d'une conversation directe */
    public function messages(Request $request)
    {
        abort_unless($this->chatEnabled(), 403);
        $me = Auth::user();
        $this->touchOnline((string) $me->id);

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
        $this->touchOnline((string) $user->id);
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

    public function heartbeat()
    {
        abort_unless($this->chatEnabled(), 403);
        $me = Auth::user();
        $this->touchOnline((string) $me->id);

        return response()->json([
            'ok' => true,
            'online_until' => now()->addSeconds(self::ONLINE_TTL_SECONDS)->toISOString(),
        ]);
    }
}

