<x-app-layout>
    <x-slot name="heading">Dashboard</x-slot>

    <div class="p-4 sm:p-6 space-y-6 pb-20 md:pb-6">

        {{-- Balance summary cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php $net = $totalOwed - $totalOwes; @endphp
            <div class="col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Net balance</p>
                <p class="text-3xl font-bold {{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                    {{ $net >= 0 ? '+' : '-' }}₹{{ number_format(abs($net) / 100, 2) }}
                </p>
                <p class="mt-1 text-xs text-gray-400">{{ $net >= 0 ? 'others owe you' : 'you owe others' }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">You are owed</p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format($totalOwed / 100, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">You owe</p>
                <p class="text-2xl font-bold text-red-500">₹{{ number_format($totalOwes / 100, 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Recent groups --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">Groups</h2>
                    <a href="{{ route('groups.index') }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">View all</a>
                </div>
                @forelse ($groups as $group)
                    <a href="{{ route('groups.show', $group->_id) }}"
                       class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                            <span class="text-emerald-700 dark:text-emerald-400 font-bold text-sm">{{ strtoupper(substr($group->name, 0, 2)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $group->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($group->members ?? []) }} members</p>
                        </div>
                        <x-icon.chevron-right class="w-4 h-4 text-gray-400" />
                    </a>
                @empty
                    <div class="p-8 text-center">
                        <x-icon.users class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">No groups yet.</p>
                        <button @click="$dispatch('open-group-modal')" class="inline-flex items-center gap-1.5 text-sm text-emerald-600 dark:text-emerald-400 font-medium hover:underline">
                            <x-icon.plus class="w-4 h-4" /> Create a group
                        </button>
                    </div>
                @endforelse
            </div>

            {{-- Recent expenses --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">Recent expenses</h2>
                    <a href="{{ route('expenses.index') }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">View all</a>
                </div>
                @forelse ($recentExpenses as $expense)
                    <a href="{{ route('expenses.show', $expense->_id) }}"
                       class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                        <div class="category-{{ $expense->category ?? 'other' }} w-2.5 h-2.5 rounded-full shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $expense->title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($expense->created_at)->diffForHumans() }}</p>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $expense->amount_formatted }}</span>
                    </a>
                @empty
                    <div class="p-8 text-center">
                        <x-icon.banknotes class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">No expenses yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent transactions --}}
        @if ($recentTransactions->count())
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Recent payments</h2>
                <a href="{{ route('transactions.index') }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">View all</a>
            </div>
            @foreach ($recentTransactions as $tx)
                @php $isFrom = $tx->from_user_id === (string) auth()->user()->_id; @endphp
                <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $isFrom ? 'bg-red-100 dark:bg-red-900/20' : 'bg-emerald-100 dark:bg-emerald-900/20' }}">
                        <x-icon.banknotes class="w-4 h-4 {{ $isFrom ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $isFrom ? 'You paid' : 'Payment received' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($tx->created_at)->diffForHumans() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold {{ $isFrom ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ $isFrom ? '-' : '+' }}{{ $tx->amount_formatted }}
                        </p>
                        <span class="text-xs px-1.5 py-0.5 rounded-full {{ $tx->status === 'completed' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' }}">
                            {{ ucfirst($tx->status) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div>
</x-app-layout>

