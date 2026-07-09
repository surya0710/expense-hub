<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Company\CompanyRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class SocialAuthController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(CompanyRegistrationService $registrationService): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::query()
            ->with('company')
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if (! $user->is_active) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'This user account is inactive. Please contact your organization admin.']);
            }

            if (! $user->google_id) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                ]);
            }

            if (! $user->isSuperAdmin() && $user->company?->accessIsBlocked()) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'This organization account is suspended. Please contact support.']);
            }

            Auth::login($user, remember: true);
            $user->update(['last_login_at' => now()]);

            if ($user->isSuperAdmin()) {
                return redirect()->route('super-admin.dashboard');
            }

            return redirect()->intended(route('dashboard'));
        }

        $matchingCompany = $registrationService->companyForEmail($googleUser->getEmail());

        if ($matchingCompany) {
            if ($matchingCompany->accessIsBlocked()) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'This organization account is suspended. Please contact support.']);
            }

            $user = $registrationService->joinCompanyFromOAuth($matchingCompany, [
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            Auth::login($user, remember: true);
            $user->update(['last_login_at' => now()]);

            return redirect()->route('dashboard')
                ->with('success', 'Welcome to '.$matchingCompany->name.'! You were added via your work email domain.');
        }

        session([
            'oauth_pending' => [
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
            ],
        ]);

        return redirect()->route('register.social');
    }
}
