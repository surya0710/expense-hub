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
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                ]);
            }

            Auth::login($user, remember: true);
            $user->update(['last_login_at' => now()]);

            return redirect()->intended(route('dashboard'));
        }

        $matchingCompany = $registrationService->companyForEmail($googleUser->getEmail());

        if ($matchingCompany) {
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
