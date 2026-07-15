<?php

namespace App\Services;

use App\Models\SystemNotification;
use App\Models\User;

class NotificationService
{
    public function send(?User $user, string $title, string $message, string $link = '/notifications'): void
    {
        if (! $user) {
            return;
        }

        SystemNotification::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
        ]);
    }
}
