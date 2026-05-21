<x-app-layout>
    <x-slot name="heading">Edit expense</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6 max-w-2xl">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
            <form method="POST" action="{{ route('expenses.update', $expense->_id) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf @method('PATCH')

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $expense->title) }}" required maxlength="200"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    @error('title') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount (INR) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₹</span>
                        <input type="number" name="amount" value="{{ old('amount', $expense->amount / 100) }}" required min="0.01" step="0.01"
                               class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    </div>
                    @error('amount') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\Expense::CATEGORIES as $cat)
                            <label class="cursor-pointer">
                                <input type="radio" name="category" value="{{ $cat }}" class="sr-only peer"
                                       {{ old('category', $expense->category) === $cat ? 'checked' : '' }}>
                                <span class="px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400
                                             peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/30 peer-checked:text-emerald-700 dark:peer-checked:text-emerald-400 transition-colors cursor-pointer">
                                    {{ ucfirst($cat) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes</label>
                    <textarea name="notes" rows="2" maxlength="1000"
                              class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition resize-none">{{ old('notes', $expense->notes) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Add attachments</label>
                    <input type="file" name="attachments[]" multiple accept="image/*,.pdf"
                           class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 dark:file:bg-emerald-900/30 file:text-emerald-700 dark:file:text-emerald-400 hover:file:bg-emerald-100 transition">
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('expenses.show', $expense->_id) }}"
                       class="flex-1 text-center py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="flex-1 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
