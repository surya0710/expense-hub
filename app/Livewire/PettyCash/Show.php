<?php

namespace App\Livewire\PettyCash;

use App\Livewire\Concerns\WithSaveFeedback;
use App\Models\PettyCashWallet;
use App\Services\PettyCash\PettyCashService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    use WithSaveFeedback;

    public PettyCashWallet $wallet;

    public bool $showTopUpModal = false;

    public bool $showSettingsModal = false;

    public string $topUpAmount = '';

    public string $topUpNote = '';

    public ?int $custodian_id = null;

    public string $balanceAmount = '';

    public string $balanceNote = '';

    public string $physicalCount = '';

    public string $reconcileNote = '';

    public ?array $reconcileResult = null;

    public function mount(PettyCashWallet $wallet): void
    {
        $this->authorize('view', $wallet);
        $this->wallet = $wallet;
        $this->custodian_id = $wallet->custodian_id;
        $this->balanceAmount = (string) $wallet->current_balance;
    }

    public function openTopUp(): void
    {
        $this->authorize('manage', $this->wallet);
        $this->reset(['topUpAmount', 'topUpNote']);
        $this->clearSaveFeedback();
        $this->showTopUpModal = true;
    }

    public function openSettings(): void
    {
        $this->authorize('manage', $this->wallet);
        $this->custodian_id = $this->wallet->custodian_id;
        $this->balanceAmount = (string) $this->wallet->current_balance;
        $this->reset(['balanceNote']);
        $this->clearSaveFeedback();
        $this->showSettingsModal = true;
    }

    public function topUp(PettyCashService $pettyCashService): void
    {
        $this->authorize('manage', $this->wallet);
        $this->clearSaveFeedback();

        $validated = $this->validate([
            'topUpAmount' => ['required', 'numeric', 'min:1'],
            'topUpNote' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $pettyCashService->topUp(
                $this->wallet,
                Auth::user(),
                (float) $validated['topUpAmount'],
                $validated['topUpNote'] ?: null,
            );
        } catch (\InvalidArgumentException $e) {
            $this->notifyFailed($e->getMessage());

            return;
        }

        $this->wallet->refresh();
        $this->balanceAmount = (string) $this->wallet->current_balance;
        $this->showTopUpModal = false;
        $this->notifySaved('Wallet topped up successfully.');
    }

    public function saveSettings(PettyCashService $pettyCashService): void
    {
        $this->authorize('manage', $this->wallet);
        $this->clearSaveFeedback();

        $validated = $this->validate([
            'custodian_id' => ['required', 'integer', 'exists:users,id'],
            'balanceAmount' => ['required', 'numeric', 'min:0'],
            'balanceNote' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            if ($validated['custodian_id'] !== $this->wallet->custodian_id) {
                $pettyCashService->updateCustodian(
                    $this->wallet,
                    Auth::user(),
                    (int) $validated['custodian_id'],
                );
            }

            $pettyCashService->setBalance(
                $this->wallet,
                Auth::user(),
                (float) $validated['balanceAmount'],
                $validated['balanceNote'] ?: null,
            );
        } catch (\InvalidArgumentException $e) {
            $this->notifyFailed($e->getMessage());

            return;
        }

        $this->wallet->refresh();
        $this->balanceAmount = (string) $this->wallet->current_balance;
        $this->showSettingsModal = false;
        $this->notifySaved('Wallet settings updated.');
    }

    public function reconcile(PettyCashService $pettyCashService): void
    {
        $this->authorize('manage', $this->wallet);

        $validated = $this->validate([
            'physicalCount' => ['required', 'numeric', 'min:0'],
            'reconcileNote' => ['nullable', 'string', 'max:500'],
        ]);

        $this->reconcileResult = $pettyCashService->reconcile(
            $this->wallet,
            Auth::user(),
            (float) $validated['physicalCount'],
            $validated['reconcileNote'] ?: null,
        );
    }

    public function applyReconciliation(PettyCashService $pettyCashService): void
    {
        $this->authorize('manage', $this->wallet);

        if (! $this->reconcileResult || $this->reconcileResult['within_tolerance']) {
            return;
        }

        $this->clearSaveFeedback();

        try {
            $pettyCashService->setBalance(
                $this->wallet,
                Auth::user(),
                (float) $this->reconcileResult['physical_count'],
                $this->reconcileNote ?: 'Reconciliation adjustment',
            );
        } catch (\InvalidArgumentException $e) {
            $this->notifyFailed($e->getMessage());

            return;
        }

        $this->wallet->refresh();
        $this->balanceAmount = (string) $this->wallet->current_balance;
        $this->reconcileResult = null;
        $this->reset(['physicalCount', 'reconcileNote']);
        $this->notifySaved('Wallet balance updated to match physical count.');
    }

    public function getTitle(): string
    {
        return $this->wallet->name;
    }

    public function render(PettyCashService $pettyCashService)
    {
        $transactions = $this->wallet->transactions()
            ->with(['expense', 'creator'])
            ->latest()
            ->limit(50)
            ->get();

        $eligibleCustodians = $pettyCashService->eligibleCustodians($this->wallet->company_id);

        if ($this->wallet->custodian && ! $eligibleCustodians->contains('id', $this->wallet->custodian_id)) {
            $eligibleCustodians = $eligibleCustodians->prepend($this->wallet->custodian);
        }

        return view('livewire.petty-cash.show', [
            'transactions' => $transactions,
            'eligibleCustodians' => $eligibleCustodians,
            'currentCustodianIneligible' => $this->wallet->custodian
                && ! $pettyCashService->isEligibleCustodian($this->wallet->custodian),
        ])->title($this->getTitle());
    }
}
