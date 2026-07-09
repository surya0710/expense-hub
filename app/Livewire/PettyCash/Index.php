<?php

namespace App\Livewire\PettyCash;

use App\Livewire\Concerns\WithSaveFeedback;
use App\Models\PettyCashWallet;
use App\Services\PettyCash\PettyCashService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Petty Cash')]
class Index extends Component
{
    use WithSaveFeedback;

    public bool $showCreateModal = false;

    public string $name = '';

    public string $site = '';

    public ?int $custodian_id = null;

    public string $opening_balance = '';

    public function mount(): void
    {
        $this->authorize('viewAny', PettyCashWallet::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', PettyCashWallet::class);
        $this->reset(['name', 'site', 'custodian_id', 'opening_balance']);
        $this->opening_balance = '50000';
        $this->clearSaveFeedback();
        $this->showCreateModal = true;
    }

    public function createWallet(PettyCashService $pettyCashService): void
    {
        $this->authorize('create', PettyCashWallet::class);
        $this->clearSaveFeedback();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255'],
            'custodian_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $pettyCashService->createWallet(Auth::user(), $validated);
        } catch (\InvalidArgumentException $e) {
            $this->notifyFailed($e->getMessage());

            return;
        }

        $this->showCreateModal = false;
        $this->notifySaved('Petty cash wallet created.');
    }

    public function render(PettyCashService $pettyCashService)
    {
        return view('livewire.petty-cash.index', [
            'wallets' => PettyCashWallet::query()
                ->with('custodian')
                ->orderBy('name')
                ->get(),
            'eligibleCustodians' => $pettyCashService->eligibleCustodians(Auth::user()->company_id),
        ]);
    }
}
