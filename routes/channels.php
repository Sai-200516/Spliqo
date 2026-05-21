<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Group;

// Private notification channel per user
Broadcast::channel('notifications.{userId}', function ($user, string $userId) {
    return (string) $user->_id === $userId;
});

// Private group channel for real-time expense updates
Broadcast::channel('group.{groupId}', function ($user, string $groupId) {
    $group = Group::find($groupId);
    return $group?->hasMember((string) $user->_id) ?? false;
});
