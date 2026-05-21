<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      x-init="$store.theme.init()"
      :class="$store.theme.dark ? 'dark' : ''">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    @php $__unread = auth()->user()->notifications()->where('is_read', false)->count(); @endphp
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="notif-unread" content="{{ $__unread }}">
    @endauth

    <title>{{ $heading ?? config('app.name', 'Spliqo') }}</title>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#10B981">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100">

{{-- Groups data island for modals (server-rendered, no extra XHR) --}}
@auth
@php
    $_mg = \App\Models\Group::where('members.user_id', (string) auth()->user()->_id)
        ->where('is_archived', false)
        ->orderByDesc('updated_at')
        ->get()
        ->map(fn($g) => [
            'id'      => (string) $g->_id,
            'name'    => $g->name,
            'members' => collect($g->members ?? [])
                ->map(fn($m) => ['user_id' => $m['user_id'] ?? '', 'name' => $m['name'] ?? 'Unknown'])
                ->values()->toArray(),
        ])->values()->toArray();
@endphp
<script id="__spliqo_groups" type="application/json">@json($_mg)</script>
@endauth

{{-- ===== Layout shell ===== --}}
<div class="flex min-h-screen" x-data="{ sidebarOpen: false }">

    {{-- ====== Sidebar (desktop permanent, mobile slide-over) ====== --}}
    {{-- Mobile backdrop --}}
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-black/40 backdrop-blur-sm md:hidden"
         style="display:none;"></div>

    {{-- Sidebar panel --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
           class="fixed md:sticky top-0 left-0 z-40 h-screen w-64 flex-shrink-0
                  bg-white dark:bg-gray-900 border-r border-gray-100 dark:border-gray-800
                  flex flex-col transition-transform duration-200 ease-in-out">

        {{-- Logo --}}
        <div class="flex items-center gap-2.5 px-5 py-5 border-b border-gray-100 dark:border-gray-800">
            <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0">
                <x-icon.banknotes class="w-4 h-4 text-white" />
            </div>
            <span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">Spliqo</span>
        </div>

        {{-- Nav links --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            @php
                $navItems = [
                    ['route' => 'dashboard',       'label' => 'Dashboard',     'icon' => 'home'],
                    ['route' => 'groups.index',    'label' => 'Groups',        'icon' => 'users'],
                    ['route' => 'expenses.index',  'label' => 'Expenses',      'icon' => 'banknotes'],
                    ['route' => 'transactions.index','label' => 'Payments',    'icon' => 'receipt-percent'],
                    ['route' => 'analytics.index', 'label' => 'Analytics',     'icon' => 'chart-bar'],
                ];
            @endphp
            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['route'].'*'); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ $active
                              ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'
                              : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100' }}">
                    <x-dynamic-component :component="'icon.' . $item['icon']" class="w-5 h-5 shrink-0" />
                    {{ $item['label'] }}
                </a>
            @endforeach

            @if (auth()->user()?->is_admin)
                <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-800">
                    <p class="px-3 mb-1.5 text-[10px] font-semibold uppercase tracking-widest text-gray-400">Admin</p>
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                              {{ request()->routeIs('admin.*') ? 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                        <x-icon.cpu-chip class="w-5 h-5 shrink-0" />
                        Admin panel
                    </a>
                </div>
            @endif
        </nav>

        {{-- User footer --}}
        <div class="p-3 border-t border-gray-100 dark:border-gray-800" x-data="{ open: false }">
            <button @click="open = !open"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-left">
                <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-sm font-semibold text-emerald-700 dark:text-emerald-400 shrink-0">
                    @if (auth()->user()?->avatar)
                        <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-8 h-8 rounded-full object-cover">
                    @else
                        {{ strtoupper(substr(auth()->user()?->name ?? '?', 0, 2)) }}
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ auth()->user()?->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()?->email }}</p>
                </div>
                <span :class="open ? 'rotate-90' : ''" class="transition-transform inline-flex">
                    <x-icon.chevron-right class="w-4 h-4 text-gray-400" />
                </span>
            </button>
            <div x-show="open" x-transition class="mt-1 space-y-0.5" style="display:none;">
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <x-icon.user-circle class="w-4 h-4" /> Profile
                </a>
                <button @click="$store.theme.toggle()"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <x-icon.moon class="w-4 h-4 dark:hidden" />
                    <x-icon.sun class="w-4 h-4 hidden dark:block" />
                    <span class="dark:hidden">Dark mode</span>
                    <span class="hidden dark:inline">Light mode</span>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <x-icon.arrow-left-on-rectangle class="w-4 h-4" /> Sign out
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ====== Main content ====== --}}
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">

        {{-- Top bar --}}
        <header class="sticky top-0 z-20 flex items-center gap-3 px-4 sm:px-6 h-14
                       bg-white/80 dark:bg-gray-900/80 backdrop-blur border-b border-gray-100 dark:border-gray-800">
            {{-- Mobile hamburger --}}
            <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <x-icon.bars-3 class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </button>

            {{-- Spliqo brand in header --}}
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0 mr-1">
                <div class="w-7 h-7 rounded-lg bg-emerald-500 flex items-center justify-center shrink-0">
                    <x-icon.banknotes class="w-3.5 h-3.5 text-white" />
                </div>
                <span class="md:hidden text-sm font-bold text-gray-900 dark:text-white">Spliqo</span>
            </a>

            {{-- Page heading --}}
            <h1 class="flex-1 text-base font-semibold text-gray-900 dark:text-gray-100">
                {{ $heading ?? '' }}
            </h1>

            {{-- Quick-action buttons --}}
            <button @click="$dispatch('open-group-modal')"
                    class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                <x-icon.users class="w-4 h-4" />
                New group
            </button>
            <button @click="$dispatch('open-expense-modal')"
                    class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">
                <x-icon.plus class="w-4 h-4" />
                Add expense
            </button>

            {{-- Notifications bell dropdown --}}
            <div class="relative" x-data="notificationPanel()" x-init="init()" @keydown.escape.window="open = false">

                {{-- Bell button --}}
                <button @click="toggle()"
                        class="relative p-2 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        :aria-expanded="open">
                    <x-icon.bell class="w-5 h-5" />
                    <span x-show="$store.notifications.unread > 0"
                          class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500"
                          style="display:none;"></span>
                </button>

                {{-- Dropdown panel --}}
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click.outside="open = false"
                     class="absolute right-0 mt-2 w-80 origin-top-right rounded-2xl shadow-xl
                            bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800
                            z-50 flex flex-col"
                     style="display:none;">

                    {{-- Panel header --}}
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">Notifications</span>
                        <button @click="markAllRead()"
                                x-show="$store.notifications.items.some(n => !n.is_read)"
                                class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">
                            Mark all read
                        </button>
                    </div>

                    {{-- Notification list --}}
                    <div class="overflow-y-auto max-h-96 divide-y divide-gray-100 dark:divide-gray-800">

                        {{-- Loading skeleton --}}
                        <template x-if="loading">
                            <div class="px-4 py-6 flex justify-center">
                                <svg class="animate-spin w-5 h-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                            </div>
                        </template>

                        {{-- Empty state --}}
                        <template x-if="!loading && $store.notifications.items.length === 0">
                            <div class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                You're all caught up!
                            </div>
                        </template>

                        {{-- Notification rows --}}
                        <template x-for="n in $store.notifications.items" :key="n.id">
                            <div class="flex gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors"
                                 :class="!n.is_read ? 'border-l-2 border-emerald-500' : 'border-l-2 border-transparent'">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="n.title"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2" x-text="n.message"></p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-text="n.created_at"></p>
                                </div>
                                <div class="flex flex-col gap-1 shrink-0">
                                    <button x-show="!n.is_read"
                                            @click="markRead(n.id)"
                                            class="p-1 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400"
                                            title="Mark read">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                    <button @click="remove(n.id)"
                                            class="p-1 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 text-red-400 dark:text-red-500"
                                            title="Delete">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Panel footer --}}
                    <div class="px-4 py-2.5 border-t border-gray-100 dark:border-gray-800">
                        <a href="{{ route('notifications.index') }}"
                           class="block text-center text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
                            View all notifications
                        </a>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if (session('success') || session('error') || $errors->isNotEmpty())
            <div class="px-4 sm:px-6 pt-4">
                @if (session('success'))
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-sm border border-emerald-200 dark:border-emerald-800 mb-2">
                        <x-icon.check-circle class="w-4 h-4 shrink-0" />
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm border border-red-200 dark:border-red-800 mb-2">
                        <x-icon.exclamation-circle class="w-4 h-4 shrink-0" />
                        {{ session('error') }}
                    </div>
                @endif
                @if ($errors->isNotEmpty())
                    <div class="px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm border border-red-200 dark:border-red-800">
                        <p class="font-semibold flex items-center gap-2 mb-1">
                            <x-icon.exclamation-circle class="w-4 h-4 shrink-0" />
                            Please fix the following:
                        </p>
                        <ul class="list-disc list-inside space-y-0.5 ml-6">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1">
            {{ $slot }}
        </main>

        {{-- Mobile bottom nav --}}
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-20 flex items-center bg-white dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800">
            @php
                $mobileNav = [
                    ['route' => 'dashboard',      'label' => 'Home',      'icon' => 'home'],
                    ['route' => 'groups.index',   'label' => 'Groups',    'icon' => 'users'],
                    ['route' => 'expenses.index', 'label' => 'Expenses',  'icon' => 'banknotes'],
                    ['route' => 'analytics.index','label' => 'Analytics', 'icon' => 'chart-bar'],
                    ['route' => 'profile.edit',   'label' => 'Profile',   'icon' => 'user-circle'],
                ];
            @endphp
            @foreach ($mobileNav as $item)
                @php $active = request()->routeIs($item['route'].'*'); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex-1 flex flex-col items-center gap-0.5 py-2.5 text-[10px] font-medium transition-colors
                          {{ $active ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">
                    <x-dynamic-component :component="'icon.' . $item['icon']" class="w-5 h-5" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</div>

{{-- ===== Add Expense Modal ===== --}}
<div x-data="__expenseModal()"
     x-show="open"
     @open-expense-modal.window="openModal($event.detail?.groupId)"
     @keydown.escape.window="close()"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
     style="display:none;">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close()"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

    {{-- Panel --}}
    <div class="relative w-full sm:max-w-lg bg-white dark:bg-gray-900 rounded-t-2xl sm:rounded-2xl shadow-2xl overflow-hidden"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Add expense</h2>
            <button @click="close()" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <x-icon.x-mark class="w-4 h-4 text-gray-500" />
            </button>
        </div>

        {{-- Body --}}
        <div class="overflow-y-auto max-h-[80vh] p-5">
            <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title <span class="text-red-500">*</span></label>
                    <input x-ref="expTitle" type="text" name="title" required maxlength="200"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                           placeholder="e.g. Dinner at Barbeque Nation">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount (INR) <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₹</span>
                            <input type="number" name="amount" x-model="amount" required min="0.01" step="0.01"
                                   class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                                   placeholder="0.00">
                        </div>
                        <label class="flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <x-icon.camera class="w-4 h-4" /><span>Scan</span>
                            <input type="file" name="receipt" class="hidden" accept="image/*" @change="ocrScan($event)">
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-emerald-600" x-show="ocrLoading" x-cloak>Scanning receipt...</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Group <span class="text-red-500">*</span></label>
                    <select name="group_id" x-model="selectedGroupId" @change="loadMembers()" required
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        <option value="">Select a group</option>
                        <template x-for="g in groups" :key="g.id">
                            <option :value="g.id" x-text="g.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\Expense::CATEGORIES as $cat)
                            <label class="cursor-pointer">
                                <input type="radio" name="category" value="{{ $cat }}" class="sr-only peer" {{ $cat === 'other' ? 'checked' : '' }}>
                                <span class="px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/30 peer-checked:text-emerald-700 dark:peer-checked:text-emerald-400 transition-colors cursor-pointer">{{ ucfirst($cat) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Paid by <span class="text-red-500">*</span></label>
                    <select name="paid_by[0][user_id]" required
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        <option value="{{ auth()->user()?->_id }}">Me ({{ auth()->user()?->name }})</option>
                        <template x-for="m in groupMembers" :key="m.user_id">
                            <option :value="m.user_id" x-text="m.name"></option>
                        </template>
                    </select>
                    <input type="hidden" name="paid_by[0][amount]" :value="Math.round(parseFloat(amount || 0) * 100)">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Split type</label>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach (\App\Models\Expense::SPLIT_TYPES as $type)
                            <label class="cursor-pointer">
                                <input type="radio" name="split_type" value="{{ $type }}" class="sr-only peer" x-model="splitType" {{ $type === 'equal' ? 'checked' : '' }}>
                                <span class="block px-2 py-2 text-center rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/30 peer-checked:text-emerald-700 dark:peer-checked:text-emerald-400 transition-colors">{{ ucfirst($type) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div x-show="groupMembers.length > 0 && splitType !== 'equal'" class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Split details
                        <span x-show="splitType === 'percentage'" class="text-gray-400 font-normal">(must total 100%)</span>
                    </label>
                    <template x-for="(m, i) in groupMembers" :key="m.user_id">
                        <div class="flex items-center gap-3">
                            <input type="hidden" :name="`splits[${i}][user_id]`" :value="m.user_id">
                            <span class="flex-1 text-sm text-gray-700 dark:text-gray-300" x-text="m.name"></span>
                            <input type="number" :name="`splits[${i}][value]`" x-model="m.splitValue"
                                   :placeholder="splitType === 'percentage' ? '%' : (splitType === 'shares' ? 'shares' : '₹')"
                                   class="w-24 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition text-right" min="0" step="0.01">
                        </div>
                    </template>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes</label>
                    <textarea name="notes" rows="2" maxlength="1000"
                              class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition resize-none"
                              placeholder="Optional notes..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tags</label>
                    <input type="text" name="tags" maxlength="200"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                           placeholder="e.g. food, trip, shared (comma-separated)">
                </div>

                <div class="flex gap-3 pt-1">
                    <button type="button" @click="close()"
                            class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">
                        Add expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Add Group Modal ===== --}}
<div x-data="{ open: false }"
     x-show="open"
     @open-group-modal.window="open = true; $nextTick(() => $refs.grpName?.focus())"
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
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Create group</h2>
            <button @click="open = false" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <x-icon.x-mark class="w-4 h-4 text-gray-500" />
            </button>
        </div>

        <div class="p-5">
            <form method="POST" action="{{ route('groups.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Group name <span class="text-red-500">*</span></label>
                    <input x-ref="grpName" type="text" name="name" required maxlength="100"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                           placeholder="e.g. Goa Trip 2025">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea name="description" rows="2" maxlength="500"
                              class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition resize-none"
                              placeholder="What is this group for?"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Currency</label>
                    <select name="currency" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        <option value="INR">INR — Indian Rupee</option>
                        <option value="USD">USD — US Dollar</option>
                        <option value="EUR">EUR — Euro</option>
                        <option value="GBP">GBP — British Pound</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Group image</label>
                    <input type="file" name="image" accept="image/*"
                           class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 dark:file:bg-emerald-900/30 file:text-emerald-700 dark:file:text-emerald-400 hover:file:bg-emerald-100 transition">
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" @click="open = false"
                            class="flex-1 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">Cancel</button>
                    <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">Create group</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Mobile FAB --}}
<div class="md:hidden fixed bottom-20 right-4 z-30 flex flex-col items-end gap-2"
     x-data="{ fabOpen: false }">
    <template x-if="fabOpen">
        <div class="flex flex-col items-end gap-2 mb-1"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <button @click="fabOpen = false; $dispatch('open-group-modal')"
                    class="flex items-center gap-2 pl-3 pr-4 py-2.5 rounded-full bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <x-icon.users class="w-4 h-4 text-emerald-500" /> New group
            </button>
            <button @click="fabOpen = false; $dispatch('open-expense-modal')"
                    class="flex items-center gap-2 pl-3 pr-4 py-2.5 rounded-full bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <x-icon.banknotes class="w-4 h-4 text-emerald-500" /> Add expense
            </button>
        </div>
    </template>
    <button @click="fabOpen = !fabOpen"
            :class="fabOpen ? 'rotate-45 bg-red-500 hover:bg-red-600' : 'bg-emerald-500 hover:bg-emerald-600'"
            class="w-12 h-12 rounded-full text-white shadow-lg flex items-center justify-center transition-all duration-200">
        <x-icon.plus class="w-5 h-5" />
    </button>
</div>

<script>
function __expenseModal() {
    return {
        open: false,
        groups: [],
        selectedGroupId: '',
        groupMembers: [],
        splitType: 'equal',
        amount: '',
        ocrLoading: false,
        init() {
            window.addEventListener('open-expense-modal', (e) => this.openModal(e.detail?.groupId));
        },
        openModal(groupId = null) {
            const el = document.getElementById('__spliqo_groups');
            this.groups = el ? JSON.parse(el.textContent) : [];
            if (groupId) {
                this.selectedGroupId = groupId;
                this.loadMembers();
            } else {
                this.selectedGroupId = '';
                this.groupMembers = [];
            }
            this.open = true;
            this.$nextTick(() => this.$refs.expTitle?.focus());
        },
        close() {
            this.open = false;
            this.selectedGroupId = '';
            this.groupMembers = [];
            this.splitType = 'equal';
            this.amount = '';
        },
        loadMembers() {
            const g = this.groups.find(g => g.id === this.selectedGroupId);
            this.groupMembers = g ? g.members.map(m => ({ ...m, splitValue: null })) : [];
        },
        async ocrScan(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.ocrLoading = true;
            const fd = new FormData();
            fd.append('receipt', file);
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            try {
                const res = await fetch('/expenses/ocr', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.amount) this.amount = data.amount;
            } finally {
                this.ocrLoading = false;
            }
        },
    };
}
</script>

@stack('scripts')

<script>
function notificationPanel() {
    return {
        open: false,
        loading: false,

        init() {
            // unread count is seeded from Alpine.store by app.js DOMContentLoaded handler
        },

        async toggle() {
            this.open = !this.open;
            if (this.open && !this.$store.notifications.loaded) {
                await this.load();
            }
        },

        async load() {
            this.loading = true;
            try {
                const res = await fetch('{{ route("notifications.feed") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await res.json();
                this.$store.notifications.items    = data.notifications;
                this.$store.notifications.unread   = data.unread_count;
                this.$store.notifications.loaded   = true;
            } finally {
                this.loading = false;
            }
        },

        async markRead(id) {
            await fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            const item = this.$store.notifications.items.find(n => n.id === id);
            if (item && !item.is_read) {
                item.is_read = true;
                this.$store.notifications.unread = Math.max(0, this.$store.notifications.unread - 1);
            }
        },

        async markAllRead() {
            await fetch('/notifications/mark-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            this.$store.notifications.items.forEach(n => n.is_read = true);
            this.$store.notifications.unread = 0;
        },

        async remove(id) {
            await fetch(`/notifications/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            const idx = this.$store.notifications.items.findIndex(n => n.id === id);
            if (idx !== -1) {
                if (!this.$store.notifications.items[idx].is_read) {
                    this.$store.notifications.unread = Math.max(0, this.$store.notifications.unread - 1);
                }
                this.$store.notifications.items.splice(idx, 1);
            }
        },
    };
}
</script>
</body>
</html>

