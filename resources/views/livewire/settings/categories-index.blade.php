<div>
    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

    <p class="mb-6 text-sm text-slate-500">Manage expense categories and cost centers used when submitting expenses.</p>

    <div class="mb-6 flex gap-2">
        <button type="button" wire:click="$set('tab', 'categories')"
            @class(['rounded-xl px-4 py-2 text-sm font-semibold', 'bg-emerald-600 text-white' => $tab === 'categories', 'border border-slate-200 text-slate-700 hover:bg-slate-50' => $tab !== 'categories'])>
            Categories
        </button>
        <button type="button" wire:click="$set('tab', 'cost_centers')"
            @class(['rounded-xl px-4 py-2 text-sm font-semibold', 'bg-emerald-600 text-white' => $tab === 'cost_centers', 'border border-slate-200 text-slate-700 hover:bg-slate-50' => $tab !== 'cost_centers'])>
            Cost centers
        </button>
    </div>

    @if($tab === 'categories')
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="font-semibold text-slate-900">Expense categories</h3>
                <button type="button" wire:click="openCategoryCreate" class="text-sm font-semibold text-emerald-600 hover:underline">+ Add category</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($categories as $category)
                            <tr wire:key="cat-{{ $category->id }}">
                                <td class="px-4 py-3">
                                    <span class="mr-2 inline-block h-3 w-3 rounded-full" style="background-color: {{ $category->color ?? '#94a3b8' }}"></span>
                                    {{ $category->name }}
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $category->code ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-emerald-100 text-emerald-700' => $category->is_active, 'bg-slate-100 text-slate-600' => ! $category->is_active])>
                                        {{ $category->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="editCategory({{ $category->id }})" class="mr-3 text-xs font-semibold text-emerald-600 hover:underline">Edit</button>
                                    <button type="button" wire:click="toggleCategory({{ $category->id }})" class="text-xs font-semibold text-slate-600 hover:underline">
                                        {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">No categories yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                <h3 class="font-semibold text-slate-900">Cost centers</h3>
                <button type="button" wire:click="openCostCenterCreate" class="text-sm font-semibold text-emerald-600 hover:underline">+ Add cost center</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($costCenters as $center)
                            <tr wire:key="cc-{{ $center->id }}">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $center->name }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $center->code ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-emerald-100 text-emerald-700' => $center->is_active, 'bg-slate-100 text-slate-600' => ! $center->is_active])>
                                        {{ $center->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="editCostCenter({{ $center->id }})" class="mr-3 text-xs font-semibold text-emerald-600 hover:underline">Edit</button>
                                    <button type="button" wire:click="toggleCostCenter({{ $center->id }})" class="text-xs font-semibold text-slate-600 hover:underline">
                                        {{ $center->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">No cost centers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($showCategoryModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" wire:click.self="$set('showCategoryModal', false)">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">{{ $editingCategoryId ? 'Edit category' : 'New category' }}</h3>
                <form wire:submit="saveCategory" class="mt-5 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                        <input wire:model="categoryName" type="text" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @error('categoryName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Color</label>
                        <input wire:model="categoryColor" type="color" class="h-10 w-full cursor-pointer rounded-xl border border-slate-200">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showCategoryModal', false)" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showCostCenterModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" wire:click.self="$set('showCostCenterModal', false)">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-slate-900">{{ $editingCostCenterId ? 'Edit cost center' : 'New cost center' }}</h3>
                <form wire:submit="saveCostCenter" class="mt-5 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                        <input wire:model="costCenterName" type="text" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        @error('costCenterName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Code</label>
                        <input wire:model="costCenterCode" type="text" placeholder="Optional" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showCostCenterModal', false)" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
