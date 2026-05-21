<x-app-layout>
    <x-slot name="heading">Groups</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6">
        <div class="flex items-center justify-between mb-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $groups->count() }} group{{ $groups->count() !== 1 ? 's' : '' }}</p>
            <button @click="$dispatch('open-group-modal')"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">
                <x-icon.plus class="w-4 h-4" />
                New group
            </button>
        </div>

        @if ($groups->isEmpty())
            <div class="text-center py-16">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4">
                    <x-icon.users class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No groups yet</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-xs mx-auto">
                    Create a group to start tracking shared expenses with your friends.
                </p>
                <button @click="$dispatch('open-group-modal')"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-500 text-white font-medium hover:bg-emerald-600 transition-colors text-sm">
                    <x-icon.plus class="w-4 h-4" /> Create your first group
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($groups as $group)
                    @php
                        $balance = $group->my_balance;
                        $net     = ($balance['net'] ?? 0);
                    @endphp
                    <a href="{{ route('groups.show', $group->_id) }}"
                       class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:border-emerald-200 dark:hover:border-emerald-800 transition-all block">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                                <span class="text-emerald-700 dark:text-emerald-400 font-bold">{{ strtoupper(substr($group->name, 0, 2)) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $group->name }}</h3>
                                @if ($group->description)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ $group->description }}</p>
                                @endif
                            </div>
                            @if ($group->is_archived)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">Archived</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400">{{ count($group->members ?? []) }} members</span>
                            <span class="text-sm font-semibold {{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                                {{ $net >= 0 ? '+' : '-' }}₹{{ number_format(abs($net) / 100, 2) }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
