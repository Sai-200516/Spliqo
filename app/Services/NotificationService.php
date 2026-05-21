<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Events\NotificationSent;

class NotificationService
{
    public function send(string $userId, string $type, string $title, string $message, array $data = []): AppNotification
    {
        $notification = AppNotification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'data'    => $data,
        ]);

        // Broadcast real-time notification via Reverb
        try {
            broadcast(new NotificationSent($userId, $notification))->toOthers();
        } catch (\Throwable) {
            // Non-fatal — notification is persisted in DB regardless
        }

        return $notification;
    }

    public function notifyGroupMembers(
        array $memberUserIds,
        string $excludeUserId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): void {
        foreach ($memberUserIds as $userId) {
            if ((string) $userId === (string) $excludeUserId) {
                continue;
            }
            $this->send((string) $userId, $type, $title, $message, $data);
        }
    }
}
