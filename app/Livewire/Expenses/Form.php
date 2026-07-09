<?php

namespace App\Livewire\Expenses;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMode;
use App\Models\Category;
use App\Models\CostCenter;
use App\Models\Expense;
use App\Models\PettyCashWallet;
use App\Services\Approval\ApprovalWorkflowService;
use App\Services\Expense\ExpenseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Form extends Component
{
    use WithFileUploads;

    public ?Expense $expense = null;

    public string $date = '';

    public string $amount = '';

    public ?int $category_id = null;

    public ?int $cost_center_id = null;

    public string $vendor_name = '';

    public string $payment_mode = 'upi';

    public ?int $wallet_id = null;

    public string $gst_percent = '';

    public string $description = '';

    public bool $reimbursable = true;

    public bool $submitNow = false;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $receipts = [];

    public function mount(?Expense $expense = null): void
    {
        $this->expense = $expense;

        if ($expense) {
            $this->authorize('update', $expense);
            $this->fillFromExpense($expense);
        } else {
            $this->authorize('create', Expense::class);
            $this->date = now()->format('Y-m-d');
        }
    }

    protected function fillFromExpense(Expense $expense): void
    {
        $this->date = $expense->date->format('Y-m-d');
        $this->amount = (string) $expense->amount;
        $this->category_id = $expense->category_id;
        $this->cost_center_id = $expense->cost_center_id;
        $this->vendor_name = $expense->vendor_name ?? '';
        $this->payment_mode = $expense->payment_mode->value;
        $this->wallet_id = $expense->wallet_id;
        $this->gst_percent = $expense->gst_percent ? (string) $expense->gst_percent : '';
        $this->description = $expense->description;
        $this->reimbursable = $expense->reimbursable;
    }

    public function updatedAmount($value): void
    {
        $limit = $this->pettyCashLimit();

        if ($limit === null) {
            return;
        }

        $amount = (float) $value;

        if ($amount > 0 && $amount <= $limit) {
            $this->payment_mode = PaymentMode::PettyCash->value;
            $this->reimbursable = false;
        } elseif ($amount > $limit && $this->payment_mode === PaymentMode::PettyCash->value) {
            $this->payment_mode = PaymentMode::Upi->value;
            $this->wallet_id = null;
        }
    }

    public function updatedPaymentMode($value): void
    {
        if ($value === PaymentMode::PettyCash->value) {
            $this->reimbursable = false;
        }
    }

    protected function pettyCashLimit(): ?float
    {
        return app(ApprovalWorkflowService::class)->pettyCashLimit(Auth::user()->company);
    }

    public function saveDraft(ExpenseService $expenseService): void
    {
        $this->submitNow = false;
        $this->save($expenseService);
    }

    public function saveAndSubmit(ExpenseService $expenseService): void
    {
        $this->submitNow = true;
        $this->save($expenseService);
    }

    public function save(ExpenseService $expenseService): void
    {
        $data = $this->validatedData();

        $user = Auth::user();

        if ($this->expense) {
            $expense = $expenseService->update($this->expense, $user, $data);
        } else {
            $expense = $expenseService->create($user, $data, submit: false);
        }

        $this->storeReceipts($expense);

        if ($this->submitNow) {
            try {
                $expenseService->submit($expense, $user);
                session()->flash('success', 'Expense submitted successfully.');
            } catch (\InvalidArgumentException $e) {
                session()->flash('error', $e->getMessage());
                $this->redirect(route('expenses.edit', $expense), navigate: true);

                return;
            }
        } else {
            session()->flash('success', $this->expense ? 'Expense updated.' : 'Expense saved as draft.');
        }

        $this->redirect(route('expenses.show', $expense), navigate: true);
    }

    protected function validatedData(): array
    {
        $validated = $this->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'cost_center_id' => [
                'nullable',
                Rule::exists('cost_centers', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'payment_mode' => ['required', Rule::enum(PaymentMode::class)],
            'wallet_id' => [
                'nullable',
                'required_if:payment_mode,petty_cash',
                Rule::exists('petty_cash_wallets', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'gst_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'reimbursable' => ['boolean'],
            'receipts.*' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,webp'],
        ]);

        $amount = (float) $validated['amount'];
        $limit = $this->pettyCashLimit();

        if ($limit !== null) {
            if ($amount <= $limit && $validated['payment_mode'] !== PaymentMode::PettyCash->value) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'payment_mode' => 'Expenses up to ₹'.number_format($limit).' must use petty cash.',
                ]);
            }

            if ($amount > $limit && $validated['payment_mode'] === PaymentMode::PettyCash->value) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'payment_mode' => 'Petty cash is only for expenses up to ₹'.number_format($limit).'.',
                ]);
            }
        }

        if ($validated['payment_mode'] === PaymentMode::PettyCash->value) {
            $validated['reimbursable'] = false;
        }

        $gstPercent = isset($validated['gst_percent']) && $validated['gst_percent'] !== ''
            ? (float) $validated['gst_percent']
            : null;

        $gstAmount = $gstPercent
            ? round($amount * $gstPercent / (100 + $gstPercent), 2)
            : null;

        return [
            'date' => $validated['date'],
            'amount' => $amount,
            'category_id' => $validated['category_id'],
            'cost_center_id' => $validated['cost_center_id'] ?? null,
            'vendor_name' => $validated['vendor_name'] ?: null,
            'payment_mode' => $validated['payment_mode'],
            'wallet_id' => $validated['payment_mode'] === PaymentMode::PettyCash->value
                ? ($validated['wallet_id'] ?? null)
                : null,
            'gst_percent' => $gstPercent,
            'gst_amount' => $gstAmount,
            'description' => $validated['description'],
            'reimbursable' => $validated['reimbursable'],
        ];
    }

    protected function storeReceipts(Expense $expense): void
    {
        foreach ($this->receipts as $file) {
            $expense->addMedia($file->getRealPath())
                ->usingFileName($file->getClientOriginalName())
                ->usingName($file->getClientOriginalName())
                ->toMediaCollection('receipts');
        }

        $this->receipts = [];
    }

    public function getTitle(): string
    {
        return $this->expense ? 'Edit expense' : 'New expense';
    }

    public function render()
    {
        $pettyCashLimit = $this->pettyCashLimit();

        return view('livewire.expenses.form', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'costCenters' => CostCenter::query()->where('is_active', true)->orderBy('name')->get(),
            'wallets' => PettyCashWallet::query()->where('is_active', true)->orderBy('name')->get(),
            'paymentModes' => PaymentMode::cases(),
            'existingReceipts' => $this->expense?->getMedia('receipts') ?? collect(),
            'pettyCashLimit' => $pettyCashLimit,
            'requiresPettyCash' => $pettyCashLimit !== null
                && $this->amount !== ''
                && (float) $this->amount > 0
                && (float) $this->amount <= $pettyCashLimit,
            'receiptRequiredAbove' => app(ApprovalWorkflowService::class)->receiptRequiredAbove(Auth::user()->company),
        ])->title($this->getTitle());
    }
}
