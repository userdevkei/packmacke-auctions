<?php

namespace App\Helpers;

use Modules\Tasks\Entities\Notification;
use Modules\Tasks\Entities\NotificationUser;

class NotificationsHelper
{
    public function unreadNotifications()
    {
        return NotificationUser::with('notification')
            ->where('user_id', auth()->id())
            ->where('is_read', 0)
            ->orderBy('id', 'DESC')
            ->get();
    }

    public function myNotifications()
    {
        return NotificationUser::with('notification')
            ->where('user_id', auth()->id())
            ->orderBy('id', 'DESC')
            ->get();
    }

    public function readStatus($notificationId)
    {
        $notification = Notification::with(['users.user'])->find($notificationId);

        $read = $notification->users->where('is_read', 1)->pluck('user');
        $unread = $notification->users->where('is_read', 0)->pluck('user');

        return [
            'read_by' => $read,
            'unread_by' => $unread
        ];
    }

}
