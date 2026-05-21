<x-app-layout>
    <x-slot name="heading">Expense detail</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6 max-w-2xl space-y-5">

        {{-- Header card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="category-{{ $expense->category ?? 'other' }} w-3.5 h-3.5 rounded-full shrink-0 mt-1"></div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $expense->title }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ ucfirst($expense->category ?? 'other') }}
                            @if ($expense->group_id)
                                &middot; {{ $group?->name }}
                            @endif
                        </p>
                    </div>
                </div>
                @if ($expense->created_by === (string) auth()->user()->_id)
                    <button @click="$dispatch('open-edit-expense-modal')"
                            class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <x-icon.pencil-square class="w-5 h-5" />
                    </button>
                @endif
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $expense->amount_formatted }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ \Carbon\Carbon::parse($expense->created_at)->format('d M Y, H:i') }}</p>
            @if ($expense->notes)
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">{{ $expense->notes }}</p>
            @endif
        </div>

        {{-- Paid by --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Paid by</h3>
            @php
                $paidByList = $expense->paid_by ?? [];
                // Normalize: old documents stored a flat dict; new ones store an array of dicts
                if (isset($paidByList['user_id'])) { $paidByList = [$paidByList]; }
            @endphp
            @foreach ($paidByList as $payer)
                @php $u = $members->firstWhere(fn($m) => (string)$m->_id === $payer['user_id']); @endphp
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                        {{ strtoupper(substr($u?->name ?? '?', 0, 2)) }}
                    </div>
                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ $u?->name ?? 'Unknown' }} {{ $payer['user_id'] === (string) auth()->user()->_id ? '(you)' : '' }}</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">₹{{ number_format($payer['amount'] / 100, 2) }}</span>
                </div>
            @endforeach
        </div>

        {{-- Splits --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Split ({{ ucfirst($expense->split_type) }})
            </h3>
            @foreach ($expense->splits ?? [] as $split)
                @php $u = $members->firstWhere(fn($m) => (string)$m->_id === $split['user_id']); @endphp
                <div class="flex items-center gap-3 py-2 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                    <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-semibold text-gray-600 dark:text-gray-300">
                        {{ strtoupper(substr($u?->name ?? '?', 0, 2)) }}
                    </div>
                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ $u?->name ?? 'Unknown' }} {{ $split['user_id'] === (string) auth()->user()->_id ? '(you)' : '' }}</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">₹{{ number_format($split['amount'] / 100, 2) }}</span>
                    <span class="{{ $split['is_settled'] ? 'text-emerald-500' : 'text-amber-500' }}">
                        @if ($split['is_settled'])
                            <x-icon.check-circle class="w-4 h-4" />
                        @else
                            <x-icon.exclamation-circle class="w-4 h-4" />
                        @endif
                    </span>
                </div>
            @endforeach
        </div>

        {{-- Attachments --}}
        @if (!empty($expense->attachments))
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Attachments</h3>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($expense->attachments as $att)
                        @php $attPath = is_array($att) ? ($att['path'] ?? '') : $att; @endphp
                        <a href="{{ Storage::url($attPath) }}" target="_blank" class="rounded-xl overflow-hidden">
                            <img src="{{ Storage::url($attPath) }}" class="w-full aspect-square object-cover hover:opacity-80 transition-opacity" alt="Attachment">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Delete --}}
        @if ($expense->created_by === (string) auth()->user()->_id)
            <form method="POST" action="{{ route('expenses.destroy', $expense->_id) }}"
                  x-data @submit.prevent="if(confirm('Delete this expense?')) $el.submit()">
                @csrf @method('DELETE')
                <button type="submit" class="w-full py-2.5 rounded-xl border border-red-200 dark:border-red-800 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    Delete expense
                </button>
            </form>
        @endif
    </div>

    {{-- Edit Expense modal --}}
    @if ($expense->created_by === (string) auth()->user()->_id)
    <div x-data="{ open: false }"
         x-show="open"
         @open-edit-expense-modal.window="open = true; $nextTick(() => $refs.expEditTitle?.focus())"
         @keydown.escape.window="open = false"
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
                <form method="POST" action="{{ route('expenses.update', $expense->_id) }}" class="space-y-4">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input x-ref="expEditTitle" type="text" name="title" value="{{ old('title', $expense->title) }}" required maxlength="200"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount (INR) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₹</span>
                            <input type="number" name="amount" value="{{ old('amount', $expense->amount / 100) }}" required min="0.01" step="0.01"
                                   class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                                   placeholder="0.00">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach (\App\Models\Expense::CATEGORIES as $cat)
                                <label class="cursor-pointer">
                                    <input type="radio" name="category" value="{{ $cat }}" class="sr-only peer"
                                           {{ old('category', $expense->category ?? 'other') === $cat ? 'checked' : '' }}>
                                    <span class="px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/30 peer-checked:text-emerald-700 dark:peer-checked:text-emerald-400 transition-colors cursor-pointer">{{ ucfirst($cat) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes</label>
                        <textarea name="notes" rows="2" maxlength="1000"
                                  class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition resize-none"
                                  placeholder="Optional notes...">{{ old('notes', $expense->notes) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tags</label>
                        <input type="text" name="tags" value="{{ old('tags', implode(', ', $expense->tags ?? [])) }}"
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
    @endif
</x-app-layout>
