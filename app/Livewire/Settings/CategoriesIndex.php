<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\WithSaveFeedback;
use App\Models\Category;
use App\Models\CostCenter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Categories')]
class CategoriesIndex extends Component
{
    use WithSaveFeedback;

    public string $tab = 'categories';

    public bool $showCategoryModal = false;

    public bool $showCostCenterModal = false;

    public ?int $editingCategoryId = null;

    public ?int $editingCostCenterId = null;

    public string $categoryName = '';

    public string $categoryColor = '#10b981';

    public string $costCenterName = '';

    public string $costCenterCode = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->can('settings.manage'), 403);
    }

    public function openCategoryCreate(): void
    {
        $this->reset(['editingCategoryId', 'categoryName']);
        $this->categoryColor = '#10b981';
        $this->showCategoryModal = true;
        $this->clearSaveFeedback();
    }

    public function editCategory(int $id): void
    {
        $category = Category::query()->findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryColor = $category->color ?? '#10b981';
        $this->showCategoryModal = true;
        $this->clearSaveFeedback();
    }

    public function saveCategory(): void
    {
        $validated = $this->validate([
            'categoryName' => ['required', 'string', 'max:255'],
            'categoryColor' => ['required', 'string', 'max:7'],
        ]);

        $payload = [
            'name' => $validated['categoryName'],
            'color' => $validated['categoryColor'],
            'is_active' => true,
        ];

        if ($this->editingCategoryId) {
            Category::query()->whereKey($this->editingCategoryId)->update($payload);
        } else {
            Category::query()->create([
                ...$payload,
                'company_id' => Auth::user()->company_id,
                'code' => Str::upper(Str::slug($validated['categoryName'], '_')),
            ]);
        }

        $this->showCategoryModal = false;
        $this->notifySaved('Category saved.');
    }

    public function toggleCategory(int $id): void
    {
        $category = Category::query()->findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);
        $this->notifySaved('Category updated.');
    }

    public function openCostCenterCreate(): void
    {
        $this->reset(['editingCostCenterId', 'costCenterName', 'costCenterCode']);
        $this->showCostCenterModal = true;
        $this->clearSaveFeedback();
    }

    public function editCostCenter(int $id): void
    {
        $center = CostCenter::query()->findOrFail($id);
        $this->editingCostCenterId = $center->id;
        $this->costCenterName = $center->name;
        $this->costCenterCode = $center->code ?? '';
        $this->showCostCenterModal = true;
        $this->clearSaveFeedback();
    }

    public function saveCostCenter(): void
    {
        $validated = $this->validate([
            'costCenterName' => ['required', 'string', 'max:255'],
            'costCenterCode' => ['nullable', 'string', 'max:50'],
        ]);

        $payload = [
            'name' => $validated['costCenterName'],
            'code' => $validated['costCenterCode'] ?: Str::upper(Str::slug($validated['costCenterName'], '_')),
            'is_active' => true,
        ];

        if ($this->editingCostCenterId) {
            CostCenter::query()->whereKey($this->editingCostCenterId)->update($payload);
        } else {
            CostCenter::query()->create([
                ...$payload,
                'company_id' => Auth::user()->company_id,
            ]);
        }

        $this->showCostCenterModal = false;
        $this->notifySaved('Cost center saved.');
    }

    public function toggleCostCenter(int $id): void
    {
        $center = CostCenter::query()->findOrFail($id);
        $center->update(['is_active' => ! $center->is_active]);
        $this->notifySaved('Cost center updated.');
    }

    public function render()
    {
        return view('livewire.settings.categories-index', [
            'categories' => Category::query()->orderBy('name')->get(),
            'costCenters' => CostCenter::query()->orderBy('name')->get(),
        ]);
    }
}
