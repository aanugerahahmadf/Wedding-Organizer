<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jeddsaliba\FilamentMessages\Models\Inbox;
use Jeddsaliba\FilamentMessages\Models\Message;
use spatie\querybuilder\QueryBuilder;

class ChatController extends Controller
{
    public function getConversations(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Fetch inboxes where the user is a participant
        $inboxes = Inbox::whereJsonContains('user_ids', $user->id)
            ->with(['messages' => function ($query): void {
                $query->latest()->limit(1);
            }])
            ->get();

        $adminUser = User::role('super_admin')->first();
        $adminId = $adminUser?->id ?? 1;
        $isAdmin = $user->hasRole('super_admin');
        $data = $inboxes->map(function ($inbox) use ($user, $adminId, $isAdmin) {
            $lastMessage = $inbox->messages->first();
            $userIds = $inbox->user_ids ?? [];
            $otherId = collect($userIds)->first(fn ($id) => (int) $id !== (int) $user->id);
            $otherUser = $otherId ? User::find($otherId) : null;

            $title = $isAdmin
                ? ($otherUser ? 'Chat dengan ' . ($otherUser->full_name ?: $otherUser->username ?: 'Customer') : $inbox->title ?? 'Chat Support')
                : 'Chat dengan Admin';
            $otherPayload = [
                'id' => $otherUser?->id ?? $adminId,
                'name' => $otherUser ? ($otherUser->full_name ?: $otherUser->username ?? 'User') : 'Admin',
                'profile_photo' => $otherUser?->avatar_url ?? null,
            ];

            return [
                'id' => $inbox->id,
                'title' => $title,
                'other_user' => $otherPayload,
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'message' => $lastMessage->message,
                    'created_at' => $lastMessage->created_at->toIso8601String(),
                    'sender_id' => $lastMessage->user_id,
                ] : null,
                'unread_count' => 0,
                'created_at' => $inbox->created_at->toIso8601String(),
                'updated_at' => $inbox->updated_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_user_is_super_admin' => $isAdmin,
        ]);
    }

    public function getMessages($inboxId)
    {
        $user = Auth::user();
        $inbox = Inbox::findOrFail($inboxId);
        $userIds = $inbox->user_ids ?? [];
        if (! in_array((int) $user->id, array_map('intval', $userIds))) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $messages = Message::where('inbox_id', $inbox->id)
            ->with('sender')
            ->oldest()
            ->get();

        $data = $messages->map(function ($message) use ($user) {
            $sender = $message->sender;
            return [
                'id' => $message->id,
                'message' => $message->message,
                'sender_id' => $message->user_id,
                'sender_name' => $sender?->full_name ?? $sender?->username ?? 'Unknown',
                'is_me' => $message->user_id === $user->id,
                'read_by' => [],
                'attachments' => [],
                'created_at' => $message->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'inbox_id' => 'required',
            'message' => 'required|string',
        ]);

        $user = Auth::user();
        $inbox = Inbox::find($request->inbox_id);
        if (! $inbox) {
            return response()->json(['success' => false, 'message' => 'Inbox not found'], 404);
        }
        $userIds = $inbox->user_ids ?? [];
        if (! in_array((int) $user->id, array_map('intval', $userIds))) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $message = Message::create([
            'inbox_id' => $request->inbox_id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        return response()->json([
            'success' => true,
            'data' => $message,
        ], 201);
    }

    public function startConversation(Request $request)
    {
        $user = Auth::user();
        $adminUser = User::role('super_admin')->first();
        $adminId = $adminUser ? (int) $adminUser->id : 1;
        $isAdmin = $user->hasRole('super_admin');

        if ($isAdmin) {
            // Superadmin chat dengan user/customer — wajib kirim with_user_id
            $withUserId = $request->input('with_user_id');
            if (! $withUserId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilih customer untuk memulai chat.',
                ], 400);
            }
            $withUserId = (int) $withUserId;
            if ($withUserId === (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak bisa chat dengan diri sendiri.',
                ], 400);
            }
            $inbox = Inbox::whereJsonContains('user_ids', (int) $user->id)
                ->whereJsonContains('user_ids', $withUserId)
                ->first();
            if (! $inbox) {
                $inbox = Inbox::create([
                    'user_ids' => [(int) $user->id, $withUserId],
                    'title' => 'Support Chat',
                ]);
            }
        } else {
            // Customer chat dengan admin/superadmin
            $inbox = Inbox::whereJsonContains('user_ids', (int) $user->id)
                ->whereJsonContains('user_ids', $adminId)
                ->first();
            if (! $inbox) {
                $inbox = Inbox::create([
                    'user_ids' => [(int) $user->id, $adminId],
                    'title' => 'Support Chat',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $inbox->id,
            ],
        ], 201);
    }

    /**
     * Daftar customer untuk superadmin (pilih siapa yang mau diajak chat).
     */
    public function getCustomersForChat(Request $request)
    {
        $user = Auth::user();
        if (! $user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $customers = User::role('customer')
            ->where('id', '!=', $user->id)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'username', 'email']);
        return response()->json([
            'success' => true,
            'data' => $customers->map(fn ($u) => [
                'id' => $u->id,
                'full_name' => $u->full_name,
                'username' => $u->username,
                'email' => $u->email,
            ]),
        ]);
    }

    public function getUnreadCount()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => 0,
            ],
        ]);
    }
}
