<x-app-layout>
    <x-slot name="heading">Notifications</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6 max-w-2xl">

        <div class="flex items-center justify-between mb-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $notifications->total() }} notification{{ $notifications->total() !== 1 ? 's' : '' }}</p>
            @if ($notifications->count())
                <form method="POST" action="{{ route('notifications.mark-all') }}">
                    @csrf
                    <button type="submit" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline font-medium">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>

        @forelse ($notifications as $notif)
            <div class="flex gap-3 bg-white dark:bg-gray-800 rounded-2xl border {{ $notif->is_read ? 'border-gray-100 dark:border-gray-700' : 'border-emerald-200 dark:border-emerald-800' }} p-4 mb-3">
                <div class="w-2.5 h-2.5 rounded-full mt-1.5 shrink-0 {{ $notif->is_read ? 'bg-gray-200 dark:bg-gray-600' : 'bg-emerald-500' }}"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $notif->title }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ $notif->message }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}</p>
                </div>
                <div class="flex flex-col gap-1.5 shrink-0">
                    @if (!$notif->is_read)
                        <form method="POST" action="{{ route('notifications.read', $notif->_id) }}">
                            @csrf
                            <button type="submit" class="p-1.5 rounded-lg text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors" title="Mark as read">
                                <x-icon.check-circle class="w-4 h-4" />
                            </button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('notifications.destroy', $notif->_id) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Delete">
                            <x-icon.trash class="w-4 h-4" />
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-16">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4">
                    <x-icon.bell class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No notifications</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">You are all caught up.</p>
            </div>
        @endforelse

        {{ $notifications->links() }}
    </div>
</x-app-layout>
