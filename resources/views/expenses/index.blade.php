<x-app-layout>
    <x-slot name="heading">Expenses</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6">
        {{-- Filters bar --}}
        <form method="GET" class="mb-5 flex flex-wrap gap-3 items-center">
            <div class="flex-1 min-w-48 relative">
                <x-icon.magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search expenses..."
                       class="w-full pl-9 pr-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
            </div>
            <select name="group" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                <option value="">All groups</option>
                @foreach ($groups as $g)
                    <option value="{{ $g->_id }}" {{ request('group') === (string)$g->_id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>
            <select name="category" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                <option value="">All categories</option>
                @foreach (\App\Models\Expense::CATEGORIES as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">
                Filter
            </button>
            @if (request()->hasAny(['search', 'group', 'category']))
                <a href="{{ route('expenses.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Clear</a>
            @endif
        </form>

        @forelse ($expenses as $expense)
            <a href="{{ route('expenses.show', $expense->_id) }}"
               class="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 mb-3 hover:shadow-sm hover:border-emerald-200 dark:hover:border-emerald-800 transition-all">
                <div class="category-{{ $expense->category ?? 'other' }} w-3 h-3 rounded-full shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $expense->title }}</p>
                        @if ($expense->category)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ ucfirst($expense->category) }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ \Carbon\Carbon::parse($expense->created_at)->format('d M Y') }}
                        @if (isset($expense->group_id))
                            &middot;
                            @php $grp = $groups->firstWhere(fn($g) => (string)$g->_id === $expense->group_id); @endphp
                            {{ $grp?->name ?? 'Group' }}
                        @endif
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $expense->amount_formatted }}</p>
                    @php
                        $myShare = collect($expense->splits ?? [])->firstWhere('user_id', (string) auth()->user()->_id);
                    @endphp
                    @if ($myShare)
                        <p class="text-xs text-gray-400">your share: ₹{{ number_format($myShare['amount'] / 100, 2) }}</p>
                    @endif
                </div>
            </a>
        @empty
            <div class="text-center py-16">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4">
                    <x-icon.banknotes class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No expenses found</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Try adjusting your filters or add a new expense.</p>
            </div>
        @endforelse

        {{ $expenses->withQueryString()->links() }}
    </div>
</x-app-layout>
