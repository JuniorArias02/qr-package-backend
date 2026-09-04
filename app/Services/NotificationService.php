<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    /**
     * Get paginated notifications for a specific user.
     *
     * @param User $user
     * @param string $status 'all' or 'unread'
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserNotifications(User $user, string $status = 'all', int $perPage = 20): LengthAwarePaginator
    {
        $query = $user->notifications();

        if ($status === 'unread') {
            $query->unread();
        }

        return $query->paginate($perPage);
    }

    /**
     * Mark a specific notification as read.
     *
     * @param User $user
     * @param string $notificationId
     * @return void
     */
    public function markNotificationAsRead(User $user, string $notificationId): void
    {
        $notification = $user->notifications()->where('id', $notificationId)->first();
        
        if ($notification) {
            $notification->markAsRead();
        }
    }

    /**
     * Mark all notifications as read for a specific user.
     *
     * @param User $user
     * @return void
     */
    public function markAllUserNotificationsAsRead(User $user): void
    {
        $user->unreadNotifications->markAsRead();
    }
}
