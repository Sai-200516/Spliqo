<x-app-layout>
    <x-slot name="heading">Analytics</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6 space-y-6">

        {{-- Scope selector --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden text-sm font-medium">
                <a href="{{ route('analytics.index') }}"
                   class="px-4 py-2 {{ !request('group') ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} transition-colors">
                    My spending
                </a>
                @foreach ($groups as $group)
                    <a href="{{ route('analytics.index', ['group' => $group->_id]) }}"
                       class="px-4 py-2 border-l border-gray-200 dark:border-gray-700 {{ request('group') === (string)$group->_id ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} transition-colors">
                        {{ $group->name }}
                    </a>
                @endforeach
            </div>
            <div class="flex rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden text-sm font-medium">
                @foreach ([3, 6, 12] as $m)
                    <a href="{{ route('analytics.index', array_merge(request()->query(), ['months' => $m])) }}"
                       class="px-3 py-2 {{ request('months', 6) == $m ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} {{ !$loop->first ? 'border-l border-gray-200 dark:border-gray-700' : '' }} transition-colors">
                        {{ $m }}M
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Total summary --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total spent</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">₹{{ number_format($summary['total'] / 100, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Avg monthly</p>
                @php
                    $nonZero = collect($summary['monthly'])->filter(fn($v) => $v > 0);
                    $avg     = $nonZero->count() ? $nonZero->average() : 0;
                @endphp
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">₹{{ number_format($avg / 100, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Top category</p>
                @php $topCat = collect($summary['categories'])->sortDesc()->keys()->first() ?? 'N/A'; @endphp
                <p class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ ucfirst($topCat) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Monthly trend --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Monthly spending</h3>
                <canvas id="monthlyChart" height="180"></canvas>
            </div>

            {{-- Category breakdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Category breakdown</h3>
                <canvas id="categoryChart" height="180"></canvas>
            </div>
        </div>

        {{-- Per member (group only) --}}
        @if (!empty($summary['per_member']))
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Per member spending</h3>
                <div class="space-y-3">
                    @php $maxSpend = max(array_values($summary['per_member']) ?: [1]); @endphp
                    @foreach ($summary['per_member'] as $userId => $spent)
                        @php $user = $groupMembers?->firstWhere(fn($u) => (string)$u->_id === $userId); @endphp
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-xs font-semibold text-emerald-700 dark:text-emerald-400 shrink-0">
                                {{ strtoupper(substr($user?->name ?? '?', 0, 2)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-700 dark:text-gray-300 truncate">{{ $user?->name ?? $userId }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 shrink-0 ml-2">₹{{ number_format($spent / 100, 2) }}</span>
                                </div>
                                <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $maxSpend > 0 ? round($spent / $maxSpend * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        const isDark = document.documentElement.classList.contains('dark');
        const gridColor  = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.05)';
        const labelColor = isDark ? '#9CA3AF' : '#6B7280';

        // Monthly chart
        const monthlyData = @json($summary['monthly']);
        new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(monthlyData),
                datasets: [{
                    label: 'Spent (₹)',
                    data: Object.values(monthlyData).map(v => v / 100),
                    backgroundColor: '#10B981',
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { grid: { color: gridColor }, ticks: { color: labelColor } },
                    x: { grid: { display: false }, ticks: { color: labelColor } },
                },
            }
        });

        // Category doughnut
        const catData = @json($summary['categories']);
        const catLabels  = Object.keys(catData).map(k => k.charAt(0).toUpperCase() + k.slice(1));
        const catValues  = Object.values(catData).map(v => v / 100);
        const catColors  = ['#10B981','#3B82F6','#F59E0B','#EF4444','#8B5CF6','#EC4899','#14B8A6','#F97316','#6366F1'];
        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{ data: catValues, backgroundColor: catColors, borderWidth: 0, hoverOffset: 6 }]
            },
            options: {
                plugins: {
                    legend: { position: 'right', labels: { color: labelColor, boxWidth: 12, padding: 12 } }
                },
                cutout: '65%',
            }
        });
    </script>
    @endpush
</x-app-layout>
