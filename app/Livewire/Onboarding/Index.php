<?php

namespace App\Livewire\Onboarding;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Get started')]
class Index extends Component
{
    public int $step = 1;

    public string $gstin = '';

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user->isOwner(), 403);

        if (! $user->company->needsOnboarding()) {
            $this->redirect(route('dashboard'), navigate: true);

            return;
        }

        $this->gstin = $user->company->gstin ?? '';
    }

    public function next(): void
    {
        if ($this->step === 2) {
            $this->validate([
                'gstin' => ['nullable', 'string', 'max:15'],
            ]);

            Auth::user()->company->update([
                'gstin' => $this->gstin ?: null,
            ]);
        }

        if ($this->step < 4) {
            $this->step++;
        }
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function complete(): void
    {
        Auth::user()->company->markOnboardingComplete();
        session()->flash('success', 'Welcome to ExpenseHub! Your workspace is ready.');
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        /** @var Company $company */
        $company = Auth::user()->company;

        return view('livewire.onboarding.index', [
            'company' => $company,
            'teamCount' => $company->users()->count(),
            'walletCount' => $company->pettyCashWallets()->count(),
            'categoryCount' => $company->categories()->count(),
            'costCenterCount' => $company->costCenters()->count(),
        ]);
    }
}
