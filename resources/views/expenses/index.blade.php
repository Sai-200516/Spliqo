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
            @php
                $grp        = $groups->firstWhere(fn($g) => (string)$g->_id === $expense->group_id);
                $grpMembers = collect($grp?->members ?? []);
                $paidByNorm = $expense->paid_by ?? [];
                if (isset($paidByNorm['user_id'])) { $paidByNorm = [$paidByNorm]; }
                $isCreator    = $expense->created_by === (string) auth()->user()->_id;
                $myShareSplit = collect($expense->splits ?? [])->firstWhere('user_id', (string) auth()->user()->_id);
                $expData = [
                    'id'        => (string) $expense->_id,
                    'title'     => $expense->title,
                    'viewAmount'=> $expense->amount_formatted,
                    'amount'    => $expense->amount / 100,
                    'category'  => $expense->category ?? 'other',
                    'splitType' => ucfirst($expense->split_type ?? 'equal'),
                    'date'      => \Carbon\Carbon::parse($expense->created_at)->format('d M Y, H:i'),
                    'notes'     => $expense->notes ?? '',
                    'tags'      => implode(', ', $expense->tags ?? []),
                    'groupName' => $grp?->name ?? '',
                    'canEdit'   => $isCreator,
                    'paidBy'    => array_map(fn($p) => [
                        'name'   => ($grpMembers->firstWhere('user_id', $p['user_id'])['name'] ?? 'Unknown') . ($p['user_id'] === (string) auth()->user()->_id ? ' (you)' : ''),
                        'amount' => '₹' . number_format($p['amount'] / 100, 2),
                    ], $paidByNorm),
                    'splits'    => array_map(fn($s) => [
                        'name'    => ($grpMembers->firstWhere('user_id', $s['user_id'])['name'] ?? 'Unknown') . ($s['user_id'] === (string) auth()->user()->_id ? ' (you)' : ''),
                        'amount'  => '₹' . number_format($s['amount'] / 100, 2),
                        'settled' => $s['is_settled'] ?? false,
                    ], $expense->splits ?? []),
                ];
            @endphp
            <div class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 mb-3 hover:shadow-sm hover:border-emerald-200 dark:hover:border-emerald-800 transition-all">
                <div class="category-{{ $expense->category ?? 'other' }} w-3 h-3 rounded-full shrink-0 mt-0.5"></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $expense->title }}</p>
                        @if ($expense->category)
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 shrink-0">{{ ucfirst($expense->category) }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ \Carbon\Carbon::parse($expense->created_at)->format('d M Y') }}
                        @if ($grp)
                            &middot; {{ $grp->name }}
                        @endif
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $expense->amount_formatted }}</p>
                    @if ($myShareSplit)
                        <p class="text-xs text-gray-400">your share: ₹{{ number_format($myShareSplit['amount'] / 100, 2) }}</p>
                    @endif
                </div>
                {{-- Action icons --}}
                <div class="flex items-center gap-0.5 ml-1 shrink-0">
                    <button @click="$dispatch('open-view-expense', @js($expData))"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors" title="View">
                        <x-icon.eye class="w-4 h-4" />
                    </button>
                    @if ($isCreator)
                        <button @click="$dispatch('open-edit-expense', @js($expData))"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" title="Edit">
                            <x-icon.pencil-square class="w-4 h-4" />
                        </button>
                        <button @click="$dispatch('open-delete-expense', @js($expData))"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Delete">
                            <x-icon.trash class="w-4 h-4" />
                        </button>
                    @endif
                </div>
            </div>
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

    {{-- ===== View Expense Modal ===== --}}
    <div x-data="{
            open: false,
            exp: { paidBy: [], splits: [] },
            openModal(data) { this.exp = data; this.open = true; }
         }"
         x-show="open"
         @open-view-expense.window="openModal($event.detail)"
         @keydown.escape.window="if(open) open = false"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
         style="display:none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full sm:max-w-lg bg-white dark:bg-gray-900 rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4">
            {{-- Header --}}
            <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="exp.title"></h2>
                    <p class="text-xs text-gray-400 mt-0.5"
                       x-text="exp.category.charAt(0).toUpperCase() + exp.category.slice(1) + (exp.groupName ? ' · ' + exp.groupName : '') + ' · ' + exp.date"></p>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button x-show="exp.canEdit"
                            @click="open = false; $nextTick(() => $dispatch('open-edit-expense', exp))"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                    </button>
                    <button @click="open = false" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <x-icon.x-mark class="w-4 h-4 text-gray-500" />
                    </button>
                </div>
            </div>
            {{-- Body --}}
            <div class="overflow-y-auto max-h-[65vh] p-5 space-y-5">
                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100" x-text="exp.viewAmount"></p>
                <p x-show="exp.notes" x-text="exp.notes"
                   class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/60 rounded-xl px-4 py-3"></p>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Paid by</h3>
                    <template x-for="(p, i) in exp.paidBy" :key="i">
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-sm text-gray-700 dark:text-gray-300" x-text="p.name"></span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="p.amount"></span>
                        </div>
                    </template>
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">
                        Split (<span x-text="exp.splitType"></span>)
                    </h3>
                    <template x-for="(s, i) in exp.splits" :key="i">
                        <div class="flex items-center gap-3 py-2 border-b border-gray-50 dark:border-gray-800 last:border-0">
                            <span class="flex-1 text-sm text-gray-700 dark:text-gray-300" x-text="s.name"></span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="s.amount"></span>
                            <span :class="s.settled ? 'text-emerald-500' : 'text-amber-500'"
                                  class="text-xs font-medium" x-text="s.settled ? 'Settled' : 'Pending'"></span>
                        </div>
                    </template>
                </div>
            </div>
            {{-- Footer --}}
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 flex gap-3">
                <button x-show="exp.canEdit"
                        @click="open = false; $nextTick(() => $dispatch('open-edit-expense', exp))"
                        class="flex-1 py-2.5 rounded-xl border border-blue-200 dark:border-blue-800 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                    Edit expense
                </button>
                <button @click="open = false"
                        class="flex-1 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Edit Expense Modal ===== --}}
    <div x-data="{
            open: false,
            exp: { id: '', title: '', amount: 0, category: 'other', notes: '', tags: '' },
            openModal(data) { this.exp = data; this.open = true; }
         }"
         x-show="open"
         @open-edit-expense.window="openModal($event.detail)"
         @keydown.escape.window="if(open) open = false"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
         style="display:none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full sm:max-w-md bg-white dark:bg-gray-900 rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Edit expense</h2>
                <button @click="open = false" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <x-icon.x-mark class="w-4 h-4 text-gray-500" />
                </button>
            </div>
            <div class="overflow-y-auto max-h-[80vh] p-5">
                <form :action="'/expenses/' + exp.id" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" value="PATCH">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" x-model="exp.title" required maxlength="200"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount (INR) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₹</span>
                            <input type="number" name="amount" x-model="exp.amount" required min="0.01" step="0.01"
                                   class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                                   placeholder="0.00">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach (\App\Models\Expense::CATEGORIES as $catKey => $catLabel)
                                <label class="cursor-pointer">
                                    <input type="radio" name="category" value="{{ $catKey }}" class="sr-only peer"
                                           x-bind:checked="exp.category === '{{ $catKey }}'">
                                    <span class="px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/30 peer-checked:text-emerald-700 dark:peer-checked:text-emerald-400 transition-colors cursor-pointer">{{ $catLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes</label>
                        <textarea name="notes" rows="2" maxlength="1000" x-model="exp.notes"
                                  class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition resize-none"
                                  placeholder="Optional notes..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tags</label>
                        <input type="text" name="tags" x-model="exp.tags"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                               placeholder="food, trip, work (comma-separated)">
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="open = false"
                                class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">Cancel</button>
                        <button type="submit"
                                class="flex-1 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== Delete Expense Modal ===== --}}
    <div x-data="{
            open: false,
            exp: { id: '', title: '' },
            openModal(data) { this.exp = data; this.open = true; }
         }"
         x-show="open"
         @open-delete-expense.window="openModal($event.detail)"
         @keydown.escape.window="if(open) open = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
        <div class="relative w-full max-w-sm bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-6"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="w-12 h-12 rounded-2xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4">
                <x-icon.trash class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 text-center mb-1">Delete expense?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">
                "<span x-text="exp.title" class="font-medium text-gray-700 dark:text-gray-300"></span>" will be permanently deleted.
            </p>
            <form :action="'/expenses/' + exp.id" method="POST" x-ref="deleteForm">
                @csrf
                <input type="hidden" name="_method" value="DELETE">
            </form>
            <div class="flex gap-3">
                <button @click="open = false"
                        class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancel
                </button>
                <button @click="$refs.deleteForm.submit()"
                        class="flex-1 py-2.5 rounded-xl bg-red-500 text-white text-sm font-medium hover:bg-red-600 transition-colors">
                    Delete
                </button>
            </div>
        </div>
    </div>
</x-app-layout>

