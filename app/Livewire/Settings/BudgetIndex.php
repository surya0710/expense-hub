<?php

namespace App\Livewire\Settings;

use App\Enums\BudgetPeriod;
use App\Enums\BudgetScope;
use App\Livewire\Concerns\WithSaveFeedback;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use App\Services\Budget\BudgetService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Budgets')]
class BudgetIndex extends Component
{
    use WithSaveFeedback;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $scope = 'category';

    public ?int $category_id = null;

    public ?int $user_id = null;

    public string $amount = '';

    public string $period = 'monthly';

    public int $alert_percent = 80;

    public bool $block_at_limit = false;

    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(Auth::user()->can('budget.view') || Auth::user()->can('budget.manage'), 403);
    }

    public function openCreate(): void
    {
        abort_unless(Auth::user()->can('budget.manage'), 403);

        $this->resetForm();
        $this->showForm = true;
        $this->clearSaveFeedback();
    }

    public function edit(int $id): void
    {
        abort_unless(Auth::user()->can('budget.manage'), 403);

        $budget = Budget::query()->findOrFail($id);
        $this->editingId = $budget->id;
        $this->name = $budget->name;
        $this->scope = $budget->scope->value;
        $this->category_id = $budget->category_id;
        $this->user_id = $budget->user_id;
        $this->amount = (string) $budget->amount;
        $this->period = $budget->period->value;
        $this->alert_percent = $budget->alert_percent;
        $this->block_at_limit = $budget->block_at_limit;
        $this->is_active = $budget->is_active;
        $this->showForm = true;
        $this->clearSaveFeedback();
    }

    public function save(): void
    {
        abort_unless(Auth::user()->can('budget.manage'), 403);
        $this->clearSaveFeedback();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'in:category,user'],
            'category_id' => [
                'required_if:scope,category',
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'user_id' => [
                'required_if:scope,user',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'amount' => ['required', 'numeric', 'min:1'],
            'period' => ['required', 'in:monthly,quarterly,yearly'],
            'alert_percent' => ['required', 'integer', 'min:50', 'max:100'],
            'block_at_limit' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'scope' => BudgetScope::from($validated['scope']),
            'category_id' => $validated['scope'] === 'category' ? $validated['category_id'] : null,
            'user_id' => $validated['scope'] === 'user' ? $validated['user_id'] : null,
            'amount' => $validated['amount'],
            'period' => BudgetPeriod::from($validated['period']),
            'alert_percent' => $validated['alert_percent'],
            'block_at_limit' => $validated['block_at_limit'],
            'is_active' => $validated['is_active'],
        ];

        if ($this->editingId) {
            Budget::query()->findOrFail($this->editingId)->update($payload);
            $this->notifySaved('Budget updated.');
        } else {
            Budget::query()->create([
                ...$payload,
                'company_id' => Auth::user()->company_id,
            ]);
            $this->notifySaved('Budget created.');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        abort_unless(Auth::user()->can('budget.manage'), 403);

        Budget::query()->findOrFail($id)->delete();
        $this->notifySaved('Budget deleted.');
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->scope = 'category';
        $this->category_id = null;
        $this->user_id = null;
        $this->amount = '';
        $this->period = 'monthly';
        $this->alert_percent = config('budget.default_alert_percent', 80);
        $this->block_at_limit = false;
        $this->is_active = true;
    }

    public function render(BudgetService $budgetService)
    {
        $user = Auth::user();

        return view('livewire.settings.budget-index', [
            'budgets' => $budgetService->budgetsForUser($user),
            'canManageBudgets' => $user->can('budget.manage'),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'employees' => User::query()
                ->where('company_id', $user->company_id)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'scopes' => BudgetScope::cases(),
            'periods' => BudgetPeriod::cases(),
        ]);
    }
}
