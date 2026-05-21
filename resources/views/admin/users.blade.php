<x-app-layout>
    <x-slot name="heading">Admin — Users</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6 max-w-4xl">

        {{-- Search --}}
        <form method="GET" class="mb-5">
            <div class="flex gap-3">
                <div class="relative flex-1">
                    <x-icon.magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search by name or email..."
                           class="w-full pl-9 pr-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">
                    Search
                </button>
            </div>
        </form>

        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-700 text-left">
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">User</th>
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide hidden sm:table-cell">Joined</th>
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr class="border-b border-gray-50 dark:border-gray-700/50 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-semibold text-gray-600 dark:text-gray-300 shrink-0">
                                        {{ strtoupper(substr($u->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $u->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $u->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden sm:table-cell">
                                {{ \Carbon\Carbon::parse($u->created_at)->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap gap-1.5">
                                    @if ($u->is_admin)
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400">Admin</span>
                                    @endif
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $u->is_active !== false ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-500' }}">
                                        {{ $u->is_active !== false ? 'Active' : 'Disabled' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                @if ((string)$u->_id !== (string) auth()->user()->_id)
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('admin.users.toggle', $u->_id) }}">
                                            @csrf
                                            <button type="submit" class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline">
                                                {{ $u->is_active !== false ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>
                                        @if (!$u->is_admin)
                                            <form method="POST" action="{{ route('admin.users.make-admin', $u->_id) }}">
                                                @csrf
                                                <button type="submit" class="text-xs text-purple-600 dark:text-purple-400 hover:underline">
                                                    Make admin
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.users.delete', $u->_id) }}"
                                              x-data @submit.prevent="if(confirm('Delete user {{ $u->name }}?')) $el.submit()">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->withQueryString()->links() }}</div>
    </div>
</x-app-layout>
