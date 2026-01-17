<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google'];

    public function redirect(string $provider): RedirectResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            Log::warning('Social login failed', [
                'provider' => $provider,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->to(Filament::getLoginUrl() ?? '/admin/login')
                ->with('error', 'Accesso con Google non riuscito. Riprova.');
        }

        $user = DB::transaction(function () use ($provider, $socialUser) {
            $account = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $socialUser->getId())
                ->first();

            if ($account) {
                $account->update([
                    'email' => $socialUser->getEmail(),
                    'avatar' => $socialUser->getAvatar(),
                    'token' => $socialUser->token ?? null,
                    'refresh_token' => $socialUser->refreshToken ?? null,
                    'expires_at' => $socialUser->expiresIn ? now()->addSeconds($socialUser->expiresIn) : null,
                ]);

                return $account->user;
            }

            $user = User::query()->where('email', $socialUser->getEmail())->first();

            if (! $user) {
                $user = User::create([
                    'name' => $socialUser->getName()
                        ?: $socialUser->getNickname()
                        ?: 'Utente Google',
                    'email' => $socialUser->getEmail(),
                    'password' => Str::random(32),
                    'email_verified_at' => ($socialUser->user['email_verified'] ?? false) ? now() : null,
                ]);

                $user->assignRole('genitore');
            } elseif (! $user->email_verified_at && ($socialUser->user['email_verified'] ?? false)) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_user_id' => $socialUser->getId(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                'token' => $socialUser->token ?? null,
                'refresh_token' => $socialUser->refreshToken ?? null,
                'expires_at' => $socialUser->expiresIn ? now()->addSeconds($socialUser->expiresIn) : null,
            ]);

            return $user;
        });

        Filament::auth()->login($user, true);
        session()->regenerate();

        return redirect()->intended(Filament::getUrl());
    }
}
