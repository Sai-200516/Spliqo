<x-app-layout>
    <x-slot name="heading">Admin — Activity logs</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6 max-w-4xl">

        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-700 text-left">
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">User</th>
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Action</th>
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide hidden md:table-cell">IP</th>
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide hidden sm:table-cell">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                            <td class="px-5 py-3.5 text-gray-700 dark:text-gray-300">{{ $log->user_id }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex px-2 py-0.5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-mono">
                                    {{ $log->action }}
                                </span>
                                @if ($log->subject_type)
                                    <span class="ml-1.5 text-xs text-gray-400">{{ class_basename($log->subject_type) }} #{{ substr($log->subject_id ?? '', 0, 8) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-gray-400 hidden md:table-cell font-mono">{{ $log->ip_address }}</td>
                            <td class="px-5 py-3.5 text-xs text-gray-400 hidden sm:table-cell">
                                {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No activity logs yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
