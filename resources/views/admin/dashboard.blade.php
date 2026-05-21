<x-app-layout>
    <x-slot name="heading">Admin — Dashboard</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6 space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach ([
                ['Total users',    $stats['total_users'],    'users'],
                ['Active users',   $stats['active_users'],   'check-circle'],
                ['Total groups',   $stats['total_groups'],   'users'],
                ['Total expenses', $stats['total_expenses'], 'banknotes'],
            ] as [$label, $value, $icon])
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $label }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($value) }}</p>
                </div>
            @endforeach
        </div>

        {{-- Recent users --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Recent users</h3>
                <a href="{{ route('admin.users') }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">View all</a>
            </div>
            @foreach ($recentUsers as $u)
                <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                    <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-sm font-semibold text-gray-600 dark:text-gray-300">
                        {{ strtoupper(substr($u->name, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $u->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $u->email }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($u->is_admin)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400">Admin</span>
                        @endif
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $u->is_active !== false ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-500' }}">
                            {{ $u->is_active !== false ? 'Active' : 'Disabled' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
