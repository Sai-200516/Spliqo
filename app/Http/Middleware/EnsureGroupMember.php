<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureGroupMember
{
    public function handle(Request $request, Closure $next): mixed
    {
        $groupId = $request->route('group') instanceof \App\Models\Group
            ? (string) $request->route('group')->_id
            : $request->route('group');

        if (!$groupId) {
            abort(404);
        }

        $group = \App\Models\Group::find($groupId);
        if (!$group) {
            abort(404);
        }

        $userId = (string) $request->user()->_id;

        if (!$group->hasMember($userId)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You are not a member of this group.'], 403);
            }
            abort(403, 'You are not a member of this group.');
        }

        return $next($request);
    }
}
