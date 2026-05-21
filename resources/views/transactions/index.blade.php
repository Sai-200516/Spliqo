<x-app-layout>
    <x-slot name="heading">Payments</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6 max-w-2xl">

        @forelse ($transactions as $tx)
            @php $isFrom = $tx->from_user_id === (string) auth()->user()->_id; @endphp
            <div class="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 mb-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $isFrom ? 'bg-red-100 dark:bg-red-900/20' : 'bg-emerald-100 dark:bg-emerald-900/20' }}">
                    <x-icon.banknotes class="w-5 h-5 {{ $isFrom ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $isFrom ? 'You paid' : 'Payment received' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($tx->created_at)->format('d M Y, H:i') }}</p>
                    @if ($tx->razorpay_payment_id)
                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $tx->razorpay_payment_id }}</p>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-bold {{ $isFrom ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $isFrom ? '-' : '+' }}{{ $tx->amount_formatted }}
                    </p>
                    <span class="text-xs px-2 py-0.5 rounded-full
                        {{ $tx->status === 'completed' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' :
                           ($tx->status === 'failed' ? 'bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400' :
                           'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400') }}">
                        {{ ucfirst($tx->status) }}
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-16">
                <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mx-auto mb-4">
                    <x-icon.banknotes class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No payments yet</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Settle a balance to create your first payment.</p>
            </div>
        @endforelse

        {{ $transactions->links() }}
    </div>
</x-app-layout>
