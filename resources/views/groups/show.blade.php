<x-app-layout>
    <x-slot name="heading">{{ $group->name }}</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6 space-y-6">

        @if (session('success'))
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-sm border border-emerald-200 dark:border-emerald-800">
                <x-icon.check-circle class="w-4 h-4 shrink-0" />
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm border border-red-200 dark:border-red-800">
                <x-icon.exclamation-circle class="w-4 h-4 shrink-0" />
                {{ session('error') }}
            </div>
        @endif

        {{-- Group header --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                        <span class="text-emerald-700 dark:text-emerald-400 font-bold text-xl">{{ strtoupper(substr($group->name, 0, 2)) }}</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $group->name }}</h2>
                        @if ($group->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $group->description }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-1">{{ count($group->members ?? []) }} members · {{ $group->currency }}</p>
                    </div>
                </div>
                @if ($group->isAdmin((string) auth()->user()->_id))
                    <button @click="$dispatch('open-edit-group-modal')"
                            class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <x-icon.pencil-square class="w-5 h-5" />
                    </button>
                @endif
            </div>
        </div>

        {{-- Your balance in this group --}}
        <div class="grid grid-cols-3 gap-3">
            @php $net = $balance['net']; @endphp
            <div class="col-span-3 sm:col-span-1 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Net</p>
                <p class="text-xl font-bold {{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                    {{ $net >= 0 ? '+' : '-' }}₹{{ number_format(abs($net) / 100, 2) }}
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Owed to you</p>
                <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format($balance['total_owed'] / 100, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">You owe</p>
                <p class="text-lg font-bold text-red-500">₹{{ number_format($balance['total_owes'] / 100, 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Members --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Members</h3>
                    @if ($group->isAdmin((string) auth()->user()->_id))
                        <button x-data @click="document.getElementById('invite-modal').classList.remove('hidden')"
                                class="flex items-center gap-1.5 text-sm text-emerald-600 dark:text-emerald-400 font-medium hover:underline">
                            <x-icon.plus class="w-4 h-4" /> Invite
                        </button>
                    @endif
                </div>
                @foreach ($group->members ?? [] as $member)
                    @php
                        $memberUser = $members->firstWhere(fn($u) => (string) $u->_id === $member['user_id']);
                        $isMe = $member['user_id'] === (string) auth()->user()->_id;
                    @endphp
                    <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                        <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ strtoupper(substr($member['name'] ?? '?', 0, 2)) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ $member['name'] ?? 'Unknown' }} {{ $isMe ? '(you)' : '' }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">{{ $member['email'] ?? '' }}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $member['role'] === 'admin' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                            {{ ucfirst($member['role'] ?? 'member') }}
                        </span>
                        @if ($group->isAdmin((string) auth()->user()->_id) && !$isMe)
                            <form method="POST" action="{{ route('groups.members.remove', [$group->_id, $member['user_id']]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1 text-gray-400 hover:text-red-500 transition-colors" title="Remove member">
                                    <x-icon.x-mark class="w-4 h-4" />
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Balances (simplified) --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Who owes whom</h3>
                </div>
                @forelse ($balances as $b)
                    @php
                        $from = $members->firstWhere(fn($u) => (string) $u->_id === $b->from_user_id);
                        $to   = $members->firstWhere(fn($u) => (string) $u->_id === $b->to_user_id);
                        $isMe = $b->from_user_id === (string) auth()->user()->_id;
                    @endphp
                    <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                        <div class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                            <span class="{{ $isMe ? 'font-semibold text-gray-900 dark:text-gray-100' : '' }}">
                                {{ $isMe ? 'You' : ($from?->name ?? 'Someone') }}
                            </span>
                            <span class="text-gray-400 mx-1">→</span>
                            {{ $b->to_user_id === (string) auth()->user()->_id ? 'you' : ($to?->name ?? 'Someone') }}
                        </div>
                        <span class="text-sm font-semibold {{ $isMe ? 'text-red-500' : 'text-gray-700 dark:text-gray-300' }}">
                            ₹{{ number_format($b->amount / 100, 2) }}
                        </span>
                        @if ($isMe)
                            <button
                                x-data
                                @click="$dispatch('open-payment', { toUserId: '{{ $b->to_user_id }}', groupId: '{{ $group->_id }}', amount: {{ $b->amount }} })"
                                class="text-xs px-2.5 py-1 rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors font-medium">
                                Settle
                            </button>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <x-icon.check-circle class="w-8 h-8 text-emerald-400 mx-auto mb-2" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">Everyone is settled up.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Add expense button --}}
        <div>
            <button @click="$dispatch('open-expense-modal', { groupId: '{{ $group->_id }}' })"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-500 text-white font-medium hover:bg-emerald-600 transition-colors text-sm">
                <x-icon.plus class="w-4 h-4" />
                Add expense to this group
            </button>
        </div>
    </div>

    {{-- Recent expenses --}}
    <div class="mt-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Recent expenses</h2>
            <a href="{{ route('expenses.index', ['group' => $group->_id]) }}"
               class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-medium">See all</a>
        </div>

        @forelse ($expenses as $expense)
            <a href="{{ route('expenses.show', $expense->_id) }}"
               class="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 mb-3 hover:shadow-sm hover:border-emerald-200 dark:hover:border-emerald-800 transition-all">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                    <x-icon.banknotes class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $expense->title }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ \Carbon\Carbon::parse($expense->created_at)->format('d M Y') }}
                        @if ($expense->category)
                            &middot; <span class="capitalize">{{ $expense->category }}</span>
                        @endif
                    </p>
                </div>
                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 shrink-0">
                    {{ $group->currency ?? 'INR' }} {{ $expense->amount_formatted }}
                </span>
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 p-6 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">No expenses yet — add the first one above.</p>
            </div>
        @endforelse
    </div>

    {{-- Edit Group modal --}}
    @if ($group->isAdmin((string) auth()->user()->_id))
    <div x-data="{ open: false }"
         x-show="open"
         @open-edit-group-modal.window="open = true; $nextTick(() => $refs.egrpName?.focus())"
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
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Edit group</h2>
                <button @click="open = false" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <x-icon.x-mark class="w-4 h-4 text-gray-500" />
                </button>
            </div>
            <div class="p-5">
                <form method="POST" action="{{ route('groups.update', $group->_id) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Group name <span class="text-red-500">*</span></label>
                        <input x-ref="egrpName" type="text" name="name" value="{{ old('name', $group->name) }}" required maxlength="100"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                        <textarea name="description" rows="2" maxlength="500"
                                  class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition resize-none">{{ old('description', $group->description) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Currency</label>
                        <select name="currency" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                            <option value="INR" {{ old('currency', $group->currency) === 'INR' ? 'selected' : '' }}>INR — Indian Rupee</option>
                            <option value="USD" {{ old('currency', $group->currency) === 'USD' ? 'selected' : '' }}>USD — US Dollar</option>
                            <option value="EUR" {{ old('currency', $group->currency) === 'EUR' ? 'selected' : '' }}>EUR — Euro</option>
                            <option value="GBP" {{ old('currency', $group->currency) === 'GBP' ? 'selected' : '' }}>GBP — British Pound</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Group image</label>
                        <input type="file" name="image" accept="image/*"
                               class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 dark:file:bg-emerald-900/30 file:text-emerald-700 dark:file:text-emerald-400 hover:file:bg-emerald-100 transition">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_archived" id="modal_is_archived" value="1" {{ $group->is_archived ? 'checked' : '' }}
                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <label for="modal_is_archived" class="text-sm text-gray-700 dark:text-gray-300">Archive this group</label>
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

    {{-- Invite modal --}}
    <div id="invite-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" x-data>
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="document.getElementById('invite-modal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Invite member</h3>
            <form method="POST" action="{{ route('groups.invite', $group->_id) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email address</label>
                    <input type="email" name="email" required placeholder="friend@example.com"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="document.getElementById('invite-modal').classList.add('hidden')"
                            class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">
                        Send invite
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Payment modal --}}
    <div x-data="paymentModal()" x-show="open" @open-payment.window="openModal($event.detail)"
         class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 w-full max-w-sm">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Settle payment</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Pay ₹<span x-text="(amount/100).toFixed(2)"></span> via Razorpay</p>
            <div class="flex gap-3">
                <button @click="open = false" class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancel
                </button>
                <button @click="pay()" class="flex-1 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">
                    Pay now
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        function paymentModal() {
            return {
                open: false,
                toUserId: null,
                groupId: null,
                amount: 0,
                openModal(detail) {
                    this.toUserId = detail.toUserId;
                    this.groupId  = detail.groupId;
                    this.amount   = detail.amount;
                    this.open     = true;
                },
                async pay() {
                    const res = await fetch('/transactions/initiate', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ to_user_id: this.toUserId, group_id: this.groupId, amount: this.amount }),
                    });
                    const data = await res.json();
                    const rzp = new Razorpay({
                        key: data.order.key_id,
                        amount: data.order.amount,
                        currency: data.order.currency,
                        order_id: data.order.order_id,
                        name: 'Spliqo',
                        description: 'Expense settlement',
                        handler: async (response) => {
                            await fetch('/transactions/verify', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                                body: JSON.stringify({ ...response, transaction_id: data.transaction_id }),
                            });
                            window.location.reload();
                        },
                    });
                    this.open = false;
                    rzp.open();
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
