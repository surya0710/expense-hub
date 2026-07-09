<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public function mount(Expense $expense): void
    {
        $this->authorize('view', $expense);

        $this->redirect(route('expenses.index', ['expense' => $expense->id]), navigate: true);
    }

    public function render()
    {
        return view('livewire.expenses.show');
    }
}
