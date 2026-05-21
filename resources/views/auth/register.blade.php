<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-950 px-4 py-12">

        <a href="/" class="mb-8 flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center">
                <x-icon.banknotes class="w-5 h-5 text-white" />
            </div>
            <span class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Spliqo</span>
        </a>

        <div class="w-full max-w-sm">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-8">
                <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-1">Create your account</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Start splitting expenses for free</p>

                {{-- Google OAuth --}}
                <a href="{{ route('auth.google') }}"
                   class="w-full flex items-center justify-center gap-3 py-2.5 px-4 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors mb-5">
                    <svg class="w-4 h-4" viewBox="0 0 48 48" fill="none">
                        <path d="M47.532 24.552c0-1.636-.142-3.2-.406-4.7H24v8.896h13.164c-.57 3.048-2.284 5.63-4.866 7.364v6.12h7.876c4.606-4.24 7.358-10.494 7.358-17.68z" fill="#4285F4"/>
                        <path d="M24 48c6.612 0 12.154-2.192 16.204-5.944l-7.876-6.12c-2.192 1.468-4.996 2.332-8.328 2.332-6.404 0-11.832-4.326-13.774-10.132H2.084v6.32C6.116 42.67 14.424 48 24 48z" fill="#34A853"/>
                        <path d="M10.226 28.136A14.44 14.44 0 0 1 9.6 24c0-1.43.246-2.818.626-4.136v-6.32H2.084A23.994 23.994 0 0 0 0 24c0 3.876.928 7.544 2.084 10.456l8.142-6.32z" fill="#FBBC05"/>
                        <path d="M24 9.732c3.612 0 6.848 1.242 9.396 3.682l7.04-7.04C36.146 2.442 30.604 0 24 0 14.424 0 6.116 5.33 2.084 13.544l8.142 6.32C12.168 14.058 17.596 9.732 24 9.732z" fill="#EA4335"/>
                    </svg>
                    Continue with Google
                </a>

                <div class="relative flex items-center gap-3 mb-5">
                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                    <span class="text-xs text-gray-400">or</span>
                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Full name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required autofocus
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        @error('name') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                               placeholder="you@example.com">
                        @error('email') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                        <input type="password" name="password" required
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        @error('password') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Confirm password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-semibold hover:bg-emerald-600 transition-colors">
                        Create account
                    </button>
                </form>
            </div>
            <p class="mt-5 text-center text-sm text-gray-500 dark:text-gray-400">
                Already have an account?
                <a href="{{ route('login') }}" class="text-emerald-600 dark:text-emerald-400 font-medium hover:underline">Sign in</a>
            </p>
        </div>
    </div>
</x-guest-layout>

