<x-app-layout>
    <x-slot name="heading">New expense</x-slot>

    <div class="p-4 sm:p-6 pb-20 md:pb-6 max-w-2xl">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">

            <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data"
                  x-data="expenseForm()" class="space-y-5">
                @csrf

                {{-- Title --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required maxlength="200"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                           placeholder="e.g. Dinner at Barbeque Nation">
                    @error('title') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Amount --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount (INR) <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₹</span>
                            <input type="number" name="amount" id="amount-input" value="{{ old('amount') }}" required min="0.01" step="0.01"
                                   x-model="amountDisplay"
                                   class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"
                                   placeholder="0.00">
                        </div>
                        {{-- OCR button --}}
                        <label class="flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <x-icon.camera class="w-4 h-4" />
                            <span>Scan</span>
                            <input type="file" class="hidden" accept="image/*" @change="ocrScan($event)">
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-gray-400" x-show="ocrLoading">Scanning receipt...</p>
                    @error('amount') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Group --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Group <span class="text-red-500">*</span></label>
                    <select name="group_id" x-model="selectedGroupId" @change="loadMembers()" required
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        <option value="">Select a group</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->_id }}" {{ (old('group_id', request('group')) === (string)$group->_id) ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    @error('group_id') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Category --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\Expense::CATEGORIES as $cat)
                            <label class="cursor-pointer">
                                <input type="radio" name="category" value="{{ $cat }}" class="sr-only peer" {{ old('category', 'other') === $cat ? 'checked' : '' }}>
                                <span class="px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400
                                             peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/30 peer-checked:text-emerald-700 dark:peer-checked:text-emerald-400 transition-colors cursor-pointer">
                                    {{ ucfirst($cat) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('category') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Paid by --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Paid by <span class="text-red-500">*</span></label>
                    <select name="paid_by[0][user_id]" required
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        <option value="{{ auth()->user()->_id }}">Me ({{ auth()->user()->name }})</option>
                        <template x-for="m in groupMembers" :key="m.user_id">
                            <option :value="m.user_id" :selected="m.user_id === '{{ auth()->user()->_id }}'"
                                    x-text="m.name + (m.user_id === '{{ auth()->user()->_id }}' ? ' (you)' : '')"></option>
                        </template>
                    </select>
                    <input type="hidden" name="paid_by[0][amount]" x-bind:value="Math.round(parseFloat(amountDisplay || 0) * 100)">
                </div>

                {{-- Split type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Split type <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach (\App\Models\Expense::SPLIT_TYPES as $type)
                            <label class="cursor-pointer">
                                <input type="radio" name="split_type" value="{{ $type }}" class="sr-only peer"
                                       x-model="splitType"
                                       {{ old('split_type', 'equal') === $type ? 'checked' : '' }}>
                                <span class="block px-3 py-2 text-center rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-medium text-gray-600 dark:text-gray-400
                                             peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/30 peer-checked:text-emerald-700 dark:peer-checked:text-emerald-400 transition-colors">
                                    {{ ucfirst($type) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Split details (dynamic) --}}
                <div x-show="groupMembers.length > 0 && splitType !== 'equal'" class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Split details
                        <span x-show="splitType === 'percentage'" class="text-gray-400">(must total 100%)</span>
                        <span x-show="splitType === 'exact'" class="text-gray-400">(must total amount)</span>
                    </label>
                    <template x-for="(m, i) in groupMembers" :key="m.user_id">
                        <div class="flex items-center gap-3">
                            <input type="hidden" :name="`splits[${i}][user_id]`" :value="m.user_id">
                            <span class="flex-1 text-sm text-gray-700 dark:text-gray-300" x-text="m.name + (m.user_id === '{{ auth()->user()->_id }}' ? ' (you)' : '')"></span>
                            <input type="number" :name="`splits[${i}][value]`" x-model="m.splitValue"
                                   :placeholder="splitType === 'percentage' ? '%' : (splitType === 'shares' ? 'shares' : '₹')"
                                   class="w-28 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 transition text-right"
                                   min="0" step="0.01">
                        </div>
                    </template>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Notes</label>
                    <textarea name="notes" rows="2" maxlength="1000"
                              class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition resize-none"
                              placeholder="Optional notes...">{{ old('notes') }}</textarea>
                </div>

                {{-- Attachments --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Attachments</label>
                    <input type="file" name="attachments[]" multiple accept="image/*,.pdf"
                           class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 dark:file:bg-emerald-900/30 file:text-emerald-700 dark:file:text-emerald-400 hover:file:bg-emerald-100 transition">
                    @error('attachments.*') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('expenses.index') }}"
                       class="flex-1 text-center py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors">
                        Add expense
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        const groupsData = @json($groups->mapWithKeys(fn($g) => [$g->_id => collect($g->members ?? [])->map(fn($m) => ['user_id' => $m['user_id'], 'name' => $m['name'], 'splitValue' => null])->values()]));

        function expenseForm() {
            return {
                selectedGroupId: '{{ old('group_id', request('group')) }}',
                groupMembers: [],
                splitType: '{{ old('split_type', 'equal') }}',
                amountDisplay: '{{ old('amount') }}',
                ocrLoading: false,
                init() {
                    if (this.selectedGroupId) this.loadMembers();
                },
                loadMembers() {
                    this.groupMembers = groupsData[this.selectedGroupId] || [];
                },
                async ocrScan(e) {
                    const file = e.target.files[0];
                    if (!file) return;
                    this.ocrLoading = true;
                    const fd = new FormData();
                    fd.append('receipt', file);
                    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    const res = await fetch('/expenses/ocr', { method: 'POST', body: fd });
                    const data = await res.json();
                    this.ocrLoading = false;
                    if (data.amount) this.amountDisplay = data.amount;
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
