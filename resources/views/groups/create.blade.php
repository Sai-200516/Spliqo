<x-app-layout>
    <x-slot name="heading">New group</x-slot>

    <div class="p-4 sm:p-6 max-w-lg">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
            <form method="POST" action="{{ route('groups.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Group name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                           placeholder="e.g. Goa Trip 2025">
                    @error('name') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea name="description" rows="3" maxlength="500"
                              class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition resize-none"
                              placeholder="What is this group for?">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Currency</label>
                    <select name="currency"
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        <option value="INR" {{ old('currency', 'INR') === 'INR' ? 'selected' : '' }}>INR — Indian Rupee</option>
                        <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD — US Dollar</option>
                        <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR — Euro</option>
                        <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP — British Pound</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Group image</label>
                    <input type="file" name="image" accept="image/*"
                           class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 dark:file:bg-emerald-900/30 file:text-emerald-700 dark:file:text-emerald-400 hover:file:bg-emerald-100 transition">
                    @error('image') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('groups.index') }}"
                       class="flex-1 text-center py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">
                        Create group
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
