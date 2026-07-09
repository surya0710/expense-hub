<?php

namespace App\Livewire\Auth;

use App\Enums\Industry;
use App\Services\Company\CompanyRegistrationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Complete your account')]
class SocialRegister extends Component
{
    public string $company_name = '';

    public string $industry = 'services';

    public ?array $oauth = null;

    public function mount(): void
    {
        $this->oauth = session('oauth_pending');

        if (! $this->oauth) {
            $this->redirect(route('register'), navigate: true);
        }
    }

    public function complete(CompanyRegistrationService $registrationService): void
    {
        if (! $this->oauth) {
            return;
        }

        $validated = $this->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'industry' => ['required', Rule::enum(Industry::class)],
        ]);

        $user = $registrationService->registerFromOAuth([
            ...$this->oauth,
            ...$validated,
        ]);

        session()->forget('oauth_pending');

        Auth::login($user);
        request()->session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.social-register', [
            'industries' => Industry::cases(),
        ]);
    }
}
