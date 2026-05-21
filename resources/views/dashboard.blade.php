<x-app-layout>
    <x-slot name="heading">Dashboard</x-slot>

    {{-- Chart data island --}}
    <script type="application/json" id="__chart_data">@json($chartData)</script>

    <div class="p-4 sm:p-6 space-y-6 pb-20 md:pb-6">

        {{-- Balance summary cards with sparklines --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php $net = $totalOwed - $totalOwes; @endphp

            <div class="col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Net balance</p>
                <p class="text-3xl font-bold {{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                    {{ $net >= 0 ? '+' : '-' }}₹{{ number_format(abs($net) / 100, 2) }}
                </p>
                <p class="mt-1 text-xs text-gray-400">{{ $net >= 0 ? 'others owe you' : 'you owe others' }}</p>
                <div class="h-10 mt-3"><canvas id="spark-net"></canvas></div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">You are owed</p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">₹{{ number_format($totalOwed / 100, 2) }}</p>
                <div class="h-10 mt-3"><canvas id="spark-owed"></canvas></div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">You owe</p>
                <p class="text-2xl font-bold text-red-500">₹{{ number_format($totalOwes / 100, 2) }}</p>
                <div class="h-10 mt-3"><canvas id="spark-owes"></canvas></div>
            </div>
        </div>

        {{-- Charts grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Donut: Spending by Category --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-0.5">Spending by Category</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Last 6 months</p>
                <div class="relative h-56">
                    <canvas id="chart-category"></canvas>
                    @if(empty($chartData['categoryBreakdown']))
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <p class="text-sm text-gray-400 dark:text-gray-500">No expenses yet</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Bar: Monthly Spending --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-0.5">Monthly Spending</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Last 6 months</p>
                <div class="relative h-56">
                    <canvas id="chart-monthly"></canvas>
                </div>
            </div>

            {{-- Horizontal bar: Spending by Group --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-0.5">Spending by Group</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">All active expenses</p>
                <div class="relative h-56">
                    <canvas id="chart-groups"></canvas>
                    @if(empty($chartData['groupTotals']))
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <p class="text-sm text-gray-400 dark:text-gray-500">No expenses yet</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Line: Balance Trend --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-0.5">Balance Trend</h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">Monthly net cash flow</p>
                <div class="relative h-56">
                    <canvas id="chart-trend"></canvas>
                </div>
            </div>
        </div>

        {{-- Recent groups + expenses --}}
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

    <script type="module">
    (function () {
        const raw = document.getElementById('__chart_data');
        if (!raw || !window.Chart) return;
        const data = JSON.parse(raw.textContent);

        const isDark     = document.documentElement.classList.contains('dark');
        const labelColor = isDark ? '#9ca3af' : '#6b7280';
        const gridColor  = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

        window.Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
        window.Chart.defaults.font.size   = 12;

        // ── helpers ──────────────────────────────────────────────────────────

        function rupees(v) {
            return '₹' + Math.abs(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function shortRupees(v) {
            const abs = Math.abs(v);
            if (abs >= 100000) return '₹' + (abs / 100000).toFixed(1) + 'L';
            if (abs >= 1000)   return '₹' + (abs / 1000).toFixed(1) + 'k';
            return '₹' + abs.toFixed(0);
        }

        function sparkline(id, values, color) {
            const el = document.getElementById(id);
            if (!el) return;
            new window.Chart(el, {
                type: 'line',
                data: {
                    labels: values.map((_, i) => i),
                    datasets: [{ data: values, borderColor: color, borderWidth: 2, pointRadius: 0, fill: false, tension: 0.4 }],
                },
                options: {
                    responsive: true, maintainAspectRatio: false, animation: false,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    scales: { x: { display: false }, y: { display: false } },
                },
            });
        }

        // ── sparklines ───────────────────────────────────────────────────────
        const monthVals = Object.values(data.monthlySpending).map(v => v / 100);
        const trendVals = Object.values(data.balanceTrend).map(v => v / 100);
        sparkline('spark-net',  trendVals, '#10b981');
        sparkline('spark-owed', monthVals, '#10b981');
        sparkline('spark-owes', monthVals, '#ef4444');

        // ── Donut: Spending by Category ──────────────────────────────────────
        const catEl = document.getElementById('chart-category');
        if (catEl && Object.keys(data.categoryBreakdown).length) {
            const catColors = {
                food: '#10b981', travel: '#3b82f6', shopping: '#f59e0b',
                entertainment: '#8b5cf6', bills: '#ef4444', other: '#6b7280',
            };
            const catLabels = Object.keys(data.categoryBreakdown).map(k => k.charAt(0).toUpperCase() + k.slice(1).toLowerCase());
            const catValues = Object.values(data.categoryBreakdown).map(v => v / 100);
            const catBg     = Object.keys(data.categoryBreakdown).map(k => catColors[k.toLowerCase()] ?? '#6b7280');
            new window.Chart(catEl, {
                type: 'doughnut',
                data: { labels: catLabels, datasets: [{ data: catValues, backgroundColor: catBg, borderWidth: 0, hoverOffset: 6 }] },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '68%',
                    plugins: {
                        legend: { position: 'right', labels: { color: labelColor, boxWidth: 12, padding: 14 } },
                        tooltip: { callbacks: { label: ctx => '  ' + rupees(ctx.parsed) } },
                    },
                },
            });
        }

        // ── Bar: Monthly Spending ────────────────────────────────────────────
        const monEl = document.getElementById('chart-monthly');
        if (monEl) {
            new window.Chart(monEl, {
                type: 'bar',
                data: {
                    labels: Object.keys(data.monthlySpending),
                    datasets: [{
                        label: 'Spending',
                        data: Object.values(data.monthlySpending).map(v => v / 100),
                        backgroundColor: '#10b981', borderRadius: 6, borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => '  ' + rupees(ctx.parsed.y) } },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: labelColor } },
                        y: { grid: { color: gridColor }, ticks: { color: labelColor, callback: v => shortRupees(v) } },
                    },
                },
            });
        }

        // ── Horizontal Bar: Spending by Group ───────────────────────────────
        const grpEl = document.getElementById('chart-groups');
        if (grpEl && Object.keys(data.groupTotals).length) {
            const grpColors = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ef4444'];
            new window.Chart(grpEl, {
                type: 'bar',
                data: {
                    labels: Object.keys(data.groupTotals),
                    datasets: [{
                        label: 'Total spent',
                        data: Object.values(data.groupTotals).map(v => v / 100),
                        backgroundColor: Object.keys(data.groupTotals).map((_, i) => grpColors[i % grpColors.length]),
                        borderRadius: 6, borderSkipped: false,
                    }],
                },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => '  ' + rupees(ctx.parsed.x) } },
                    },
                    scales: {
                        x: { grid: { color: gridColor }, ticks: { color: labelColor, callback: v => shortRupees(v) } },
                        y: { grid: { display: false }, ticks: { color: labelColor } },
                    },
                },
            });
        }

        // ── Line: Balance Trend ──────────────────────────────────────────────
        const trendEl = document.getElementById('chart-trend');
        if (trendEl) {
            const tVals = Object.values(data.balanceTrend).map(v => v / 100);
            new window.Chart(trendEl, {
                type: 'line',
                data: {
                    labels: Object.keys(data.balanceTrend),
                    datasets: [{
                        label: 'Net',
                        data: tVals,
                        borderColor: '#10b981',
                        backgroundColor: function (ctx) {
                            const chart = ctx.chart;
                            const { ctx: c, chartArea } = chart;
                            if (!chartArea) return 'rgba(16,185,129,0.15)';
                            const grad = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                            grad.addColorStop(0, 'rgba(16,185,129,0.3)');
                            grad.addColorStop(1, 'rgba(16,185,129,0.02)');
                            return grad;
                        },
                        fill: true, tension: 0.4, borderWidth: 2,
                        pointBackgroundColor: '#10b981', pointRadius: 4, pointHoverRadius: 6,
                    }],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    const v = ctx.parsed.y;
                                    return '  ' + (v >= 0 ? '+' : '-') + rupees(Math.abs(v));
                                },
                            },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: labelColor } },
                        y: {
                            grid: { color: gridColor },
                            ticks: {
                                color: labelColor,
                                callback: v => (v >= 0 ? '+' : '-') + shortRupees(Math.abs(v)),
                            },
                        },
                    },
                },
            });
        }
    })();
    </script>
</x-app-layout>

