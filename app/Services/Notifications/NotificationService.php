<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;

class NotificationService
{
    /**
     * @return CursorPaginator<int, Notification>
     */
    public function listFor(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(perPage: $perPage, cursor: $cursor);
    }

    /**
     * Mark a single notification read. Scoped to the given user, so a
     * notification belonging to someone else 404s rather than leaking whether
     * that id exists.
     */
    public function markRead(User $user, int $notificationId): Notification
    {
        $notification = Notification::where('user_id', $user->id)->findOrFail($notificationId);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $notification;
    }

    /**
     * Mark every unread notification for the user as read. Returns the number
     * of rows updated.
     */
    public function markAllRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
