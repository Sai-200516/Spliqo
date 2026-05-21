<?php

namespace App\Events;

use App\Models\AppNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly AppNotification $notification
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('notifications.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'notification.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'      => (string) $this->notification->_id,
            'type'    => $this->notification->type,
            'title'   => $this->notification->title,
            'message' => $this->notification->message,
            'data'    => $this->notification->data,
        ];
    }
}
