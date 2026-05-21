<!DOCTYPE html>
<html lang="en"
      x-data
      x-init="$store.theme.init()"
      :class="{ 'dark': $store.theme.value === 'dark' }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Spliqo — Split expenses, settle debts, stress less</title>
    <meta name="description" content="Track shared expenses, split bills fairly, and settle up instantly with friends and groups. No spreadsheets. No drama.">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#10B981">

    <meta property="og:title" content="Spliqo — Expense splitting made easy">
    <meta property="og:description" content="Track shared expenses, split bills fairly, and settle up instantly.">
    <meta property="og:type" content="website">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased">

    {{-- Nav --}}
    <nav class="fixed top-0 inset-x-0 z-50 bg-white/80 dark:bg-gray-950/80 backdrop-blur-md border-b border-gray-100 dark:border-gray-800">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center gap-6">
            <a href="/" class="flex items-center gap-2.5 mr-auto">
                <div class="w-8 h-8 rounded-xl bg-emerald-500 flex items-center justify-center">
                    <span class="text-white font-bold text-sm">S</span>
                </div>
                <span class="font-bold text-lg text-gray-900 dark:text-gray-100 tracking-tight">Spliqo</span>
            </a>
            <button
                @click="$store.theme.toggle()"
                class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                aria-label="Toggle theme"
            >
                <x-icon.sun class="w-5 h-5 dark:hidden" />
                <x-icon.moon class="w-5 h-5 hidden dark:block" />
            </button>
            @auth
                <a href="{{ route('dashboard') }}"
                   class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                    Sign in
                </a>
                <a href="{{ route('register') }}"
                   class="text-sm font-medium px-4 py-2 rounded-xl bg-emerald-500 text-white hover:bg-emerald-600 transition-colors">
                    Get started
                </a>
            @endauth
        </div>
    </nav>

    {{-- Hero --}}
    <section class="pt-32 pb-24 px-4 sm:px-6 text-center">
        <div class="max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-sm font-medium mb-8 border border-emerald-200 dark:border-emerald-800">
                <x-icon.check-circle class="w-4 h-4" />
                Free to use — no credit card required
            </div>
            <h1 class="text-5xl sm:text-6xl font-extrabold tracking-tight text-gray-900 dark:text-gray-50 leading-tight">
                Split expenses.<br>
                <span class="text-emerald-500">Settle fast.</span><br>
                Stay friends.
            </h1>
            <p class="mt-6 text-xl text-gray-500 dark:text-gray-400 max-w-xl mx-auto leading-relaxed">
                Track shared bills with your groups, split costs any way you like, and pay back in one tap. No spreadsheets. No awkward reminders.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-emerald-500 text-white font-semibold hover:bg-emerald-600 transition-colors shadow-lg shadow-emerald-500/25 text-sm">
                    <x-icon.plus class="w-5 h-5" />
                    Create free account
                </a>
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-sm">
                    <x-icon.arrow-right-on-rectangle class="w-5 h-5" />
                    Sign in
                </a>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-24 px-4 sm:px-6 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Everything you need to manage shared money</h2>
                <p class="mt-4 text-gray-500 dark:text-gray-400 max-w-xl mx-auto">
                    Built for roommates, trip groups, office teams — anyone splitting costs.
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                    $features = [
                        ['icon' => 'users',                'title' => 'Group management',     'desc' => 'Create groups for trips, households, or projects. Invite members via email link.'],
                        ['icon' => 'banknotes',            'title' => 'Flexible splitting',    'desc' => 'Split equally, by percentage, exact amounts, or custom shares. Works for any scenario.'],
                        ['icon' => 'chart-bar',            'title' => 'Spending analytics',   'desc' => 'Visualise where money goes with interactive charts broken down by category and member.'],
                        ['icon' => 'receipt-percent',      'title' => 'Instant settlement',   'desc' => 'Pay outstanding balances directly in Spliqo using Razorpay. Balances update automatically.'],
                        ['icon' => 'camera',               'title' => 'Receipt scanning',     'desc' => 'Snap a photo of any receipt and OCR automatically fills in the amount for you.'],
                        ['icon' => 'bell',                 'title' => 'Smart notifications',  'desc' => 'Real-time alerts when a member adds an expense, sends payment, or joins your group.'],
                        ['icon' => 'link',                 'title' => 'Debt simplification',  'desc' => 'Automatically minimises the number of payments needed to settle a group completely.'],
                        ['icon' => 'presentation-chart-line', 'title' => 'Balance tracking', 'desc' => 'Always know who owes whom. Balances refresh the moment a new expense lands.'],
                        ['icon' => 'cpu-chip',             'title' => 'Admin panel',          'desc' => 'Site-wide statistics, user management, and audit logs for administrators.'],
                    ];
                @endphp

                @foreach ($features as $f)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center mb-4">
                            <x-dynamic-component :component="'icon.' . $f['icon']" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $f['title'] }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ $f['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="py-24 px-4 sm:px-6">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Up and running in under a minute</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                @foreach ([
                    ['step' => '1', 'title' => 'Create a group', 'desc' => 'Give it a name, invite friends by email, and you are ready.'],
                    ['step' => '2', 'title' => 'Add expenses',   'desc' => 'Log any shared cost. Spliqo splits it and tracks who owes what.'],
                    ['step' => '3', 'title' => 'Settle up',      'desc' => 'Pay outstanding balances in one tap. Done.'],
                ] as $step)
                    <div class="text-center">
                        <div class="w-12 h-12 rounded-full bg-emerald-500 text-white font-bold text-lg flex items-center justify-center mx-auto mb-4">
                            {{ $step['step'] }}
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $step['title'] }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-24 px-4 sm:px-6 bg-emerald-500">
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="text-3xl font-bold text-white">Ready to stop chasing payments?</h2>
            <p class="mt-4 text-emerald-100">Join and make shared money simple.</p>
            <a href="{{ route('register') }}"
               class="mt-8 inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-white text-emerald-700 font-semibold hover:bg-emerald-50 transition-colors text-sm shadow-lg">
                <x-icon.plus class="w-5 h-5" />
                Get started for free
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-10 px-4 sm:px-6 border-t border-gray-100 dark:border-gray-800">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-emerald-500 flex items-center justify-center">
                    <span class="text-white font-bold text-xs">S</span>
                </div>
                <span class="font-medium text-gray-700 dark:text-gray-300">Spliqo</span>
            </div>
            <p>Expense splitting made simple.</p>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors">Sign in</a>
                <a href="{{ route('register') }}" class="hover:text-gray-900 dark:hover:text-gray-100 transition-colors">Register</a>
            </div>
        </div>
    </footer>

</body>
</html>

