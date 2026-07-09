<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\WithSaveFeedback;
use App\Services\Team\TeamService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Organization')]
class CompanyProfile extends Component
{
    use WithSaveFeedback;

    public string $name = '';

    public string $gstin = '';

    public string $currency = 'INR';

    public int $fy_start_month = 4;

    public string $domain = '';

    public bool $domain_auto_join = false;

    public function mount(): void
    {
        abort_unless(Auth::user()->can('settings.manage'), 403);

        $company = Auth::user()->company;
        $this->name = $company->name;
        $this->gstin = $company->gstin ?? '';
        $this->currency = $company->currency;
        $this->fy_start_month = $company->fy_start_month;
        $this->domain = $company->domain ?? '';
        $this->domain_auto_join = (bool) $company->domain_auto_join;
    }

    public function save(TeamService $teamService): void
    {
        abort_unless(Auth::user()->can('settings.manage'), 403);

        $this->clearSaveFeedback();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:15'],
            'currency' => ['required', 'string', 'size:3'],
            'fy_start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'domain' => ['nullable', 'string', 'max:255'],
            'domain_auto_join' => ['boolean'],
        ]);

        $teamService->updateCompany(Auth::user()->company, [
            'name' => $validated['name'],
            'gstin' => $validated['gstin'],
            'currency' => $validated['currency'],
            'fy_start_month' => $validated['fy_start_month'],
        ]);

        $domainUpdates = ['domain_auto_join' => $validated['domain_auto_join']];
        if ($validated['domain'] !== '') {
            $domainUpdates['domain'] = $validated['domain'];
        }

        $teamService->updateDomainSettings(Auth::user()->company, $domainUpdates);

        $this->notifySaved('Organization profile saved successfully.');
    }

    public function render()
    {
        $company = Auth::user()->company->fresh();

        return view('livewire.settings.company-profile', [
            'company' => $company,
        ]);
    }
}
