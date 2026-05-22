<x-app-layout>
    <x-slot name="heading">Profile</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6 max-w-xl mx-auto space-y-5">

        @if (session('status') === 'profile-updated')
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-sm border border-emerald-200 dark:border-emerald-800">
                <x-icon.check-circle class="w-4 h-4 shrink-0" />
                Profile updated successfully.
            </div>
        @endif

        {{-- Profile info --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Personal info</h2>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PATCH')

                <div class="flex flex-col items-center gap-3 py-2"
                     x-data="{
                         previewUrl: '{{ addslashes($user->avatar_url ?? '') }}',
                         uploading: false,
                         uploadError: '',
                         async pick(event) {
                             const file = event.target.files[0];
                             if (!file) return;
                             this.uploadError = '';
                             this.previewUrl = URL.createObjectURL(file);
                             this.uploading = true;
                             try {
                                 const fd = new FormData();
                                 fd.append('avatar', file);
                                 fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
                                 const res = await fetch('{{ route('profile.avatar') }}', { method: 'POST', body: fd });
                                 if (res.ok) {
                                     const data = await res.json();
                                     this.previewUrl = data.avatar_url;
                                 } else {
                                     this.uploadError = 'Upload failed. Max 2 MB, images only.';
                                 }
                             } catch {
                                 this.uploadError = 'Network error. Please try again.';
                             } finally {
                                 this.uploading = false;
                             }
                         }
                     }">
                    {{-- Avatar circle with spinner overlay --}}
                    <div class="relative w-16 h-16 rounded-2xl overflow-hidden bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                        <img x-show="previewUrl" :src="previewUrl" class="w-full h-full object-cover" alt="Avatar">
                        <span x-show="!previewUrl" class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                        <div x-show="uploading" class="absolute inset-0 bg-black/40 flex items-center justify-center rounded-2xl">
                            <svg class="animate-spin w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <label class="cursor-pointer flex items-center justify-center gap-1.5 text-sm text-emerald-600 dark:text-emerald-400 font-medium hover:underline" :class="{ 'opacity-50 pointer-events-none': uploading }">
                            <x-icon.arrow-up-tray class="w-4 h-4" />
                            <span x-text="uploading ? 'Uploading…' : 'Change photo'">Change photo</span>
                            <input type="file" accept="image/*" class="hidden" @change="pick($event)" :disabled="uploading">
                        </label>
                        <p class="text-xs text-gray-400 mt-0.5 text-center">JPG, PNG up to 2MB — saved instantly</p>
                        <p x-show="uploadError" x-text="uploadError" class="text-xs text-red-500 mt-1"></p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Full name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Bio</label>
                    <textarea name="bio" rows="2" maxlength="300"
                              class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition resize-none">{{ old('bio', $user->bio) }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Timezone</label>
                        <select name="timezone" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                            @foreach (timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" {{ old('timezone', $user->timezone ?? 'Asia/Kolkata') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Currency</label>
                        <select name="preferred_currency" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                            <option value="INR" {{ old('preferred_currency', $user->preferred_currency ?? 'INR') === 'INR' ? 'selected' : '' }}>INR</option>
                            <option value="USD" {{ old('preferred_currency', $user->preferred_currency) === 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ old('preferred_currency', $user->preferred_currency) === 'EUR' ? 'selected' : '' }}>EUR</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">
                    Save changes
                </button>
            </form>
        </div>

        {{-- Theme --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Appearance</h2>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Dark mode</p>
                    <p class="text-xs text-gray-400">Easier on the eyes in low light</p>
                </div>
                <button x-data @click="$store.theme.toggle()"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                        :class="$store.theme.dark ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-600'">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                          :class="$store.theme.dark ? 'translate-x-6' : 'translate-x-1'"></span>
                </button>
            </div>
        </div>

        {{-- Change password --}}
        @if (!$user->google_id)
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Change password</h2>
                <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Current password</label>
                        <input type="password" name="current_password"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        @error('current_password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">New password</label>
                        <input type="password" name="password"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Confirm new password</label>
                        <input type="password" name="password_confirmation"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-sm font-medium hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                        Update password
                    </button>
                </form>
            </div>
        @endif

        {{-- Delete account --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-red-200 dark:border-red-800 p-6">
            <h2 class="font-semibold text-red-600 dark:text-red-400 mb-2">Delete account</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Once deleted, all your data is permanently removed. This cannot be undone.</p>
            <form method="POST" action="{{ route('profile.destroy') }}"
                  x-data @submit.prevent="if(confirm('Are you sure? This will permanently delete your account.')) $el.submit()">
                @csrf @method('DELETE')
                <input type="password" name="password" placeholder="Enter your password to confirm"
                       class="w-full mb-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 transition">
                @error('password') <p class="mb-2 text-xs text-red-500">{{ $message }}</p> @enderror
                <button type="submit" class="w-full py-2.5 rounded-xl border border-red-200 dark:border-red-800 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    Delete my account
                </button>
            </form>
        </div>
    </div>
</x-app-layout>

