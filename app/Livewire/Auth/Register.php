<?php

namespace App\Livewire\Auth;

use App\Enums\Industry;
use App\Models\Company;
use App\Services\Company\CompanyRegistrationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Create account')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $company_name = '';

    public string $industry = 'services';

    public ?Company $matchingCompany = null;

    public function updatedEmail(): void
    {
        $this->matchingCompany = app(CompanyRegistrationService::class)
            ->companyForEmail($this->email);
    }

    public function register(CompanyRegistrationService $registrationService): void
    {
        $this->matchingCompany = $registrationService->companyForEmail($this->email);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ];

        if (! $this->matchingCompany) {
            $rules['company_name'] = ['required', 'string', 'max:255'];
            $rules['industry'] = ['required', Rule::enum(Industry::class)];
        }

        $validated = $this->validate($rules);

        $joining = $this->matchingCompany;
        $user = $registrationService->register($validated);

        Auth::login($user);

        request()->session()->regenerate();

        if ($joining) {
            session()->flash('success', 'Welcome to '.$joining->name.'! You were added automatically via your work email domain.');
            $this->redirect(route('dashboard'), navigate: true);

            return;
        }

        $this->redirect(route('onboarding'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register', [
            'industries' => Industry::cases(),
        ]);
    }
}
