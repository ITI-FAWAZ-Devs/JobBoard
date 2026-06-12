<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class OAuthController extends Controller
{
    private const PROVIDERS = [
        'google' => 'google',
        'linkedin' => 'linkedin-openid',
    ];

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $driver = self::PROVIDERS[$provider] ?? null;

        if (! $driver) {
            return $this->redirectWithError('Unsupported login provider.');
        }

        $role = $request->query('role') === 'employer' ? 'employer' : 'candidate';
        $mode = $request->query('mode') === 'connect' ? 'connect' : 'login';
        $connectToken = (string) $request->query('connect_token', '');

        // Encode context in state; APP_KEY must be set for this to work.
        $state = base64_encode(json_encode([
            'role'          => $role,
            'mode'          => $mode,
            'connect_token' => $connectToken,
        ]));

        return Socialite::driver($driver)
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $driver = self::PROVIDERS[$provider] ?? null;

        if (! $driver) {
            return $this->redirectWithError('Unsupported login provider.');
        }

        if ($request->query('error')) {
            return $this->redirectWithError('Authentication was cancelled.');
        }

        $stateData = $this->decodeState($request->query('state'));
        $mode = ($stateData['mode'] ?? '') === 'connect' ? 'connect' : 'login';

        // Must match the exact registered redirect URI so the code exchange succeeds.
        $callbackUrl = (string) config('services.'.$driver.'.redirect');

        if ($mode === 'connect') {
            return $this->handleConnect($request, $provider, $driver, $stateData, $callbackUrl);
        }

        return $this->handleLogin($request, $provider, $driver, $stateData, $callbackUrl);
    }

    private function handleLogin(Request $request, string $provider, string $driver, array $stateData, string $callbackUrl): RedirectResponse
    {
        try {
            $oauthUser = Socialite::driver($driver)->stateless()->redirectUrl($callbackUrl)->user();
        } catch (Throwable) {
            return $this->redirectWithError('Failed to authenticate with '.ucfirst($provider).'. Please try again.');
        }

        if (! $oauthUser->getEmail()) {
            return $this->redirectWithError('Your '.ucfirst($provider).' account did not provide an email address.');
        }

        $role = ($stateData['role'] ?? '') === 'employer' ? 'employer' : 'candidate';

        $user = User::where('provider', $provider)
            ->where('provider_id', $oauthUser->getId())
            ->first();

        $isNewUser = false;

        if (! $user) {
            $user = User::where('email', $oauthUser->getEmail())->first();

            if ($user) {
                if (! $user->provider) {
                    $user->forceFill([
                        'provider'    => $provider,
                        'provider_id' => $oauthUser->getId(),
                    ])->save();
                }
            } else {
                $isNewUser = true;
                $user = User::create([
                    'name'     => $oauthUser->getName() ?: ($oauthUser->getNickname() ?: 'User'),
                    'email'    => $oauthUser->getEmail(),
                    'password' => Str::random(40),
                    'role'     => $role,
                ]);

                $user->forceFill([
                    'provider'           => $provider,
                    'provider_id'        => $oauthUser->getId(),
                    'email_verified_at'  => now(),
                ])->save();

                if ($user->isEmployer()) {
                    $user->employerProfile()->create(['company_name' => '']);
                }

                if ($user->isCandidate()) {
                    $user->candidateProfile()->create(
                        $this->extractProfileData($provider, $oauthUser)
                    );
                }
            }
        }

        if (! $user->is_active) {
            return $this->redirectWithError('Your account has been suspended.');
        }

        // Sync avatar from provider if user has none
        $this->syncAvatar($user, $oauthUser->getAvatar());

        // On subsequent logins, also fill any still-empty profile fields
        if (! $isNewUser && $user->isCandidate()) {
            $this->fillMissingProfileData($user, $provider, $oauthUser);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return redirect()->away($this->frontendUrl('/oauth/callback?'.http_build_query([
            'token' => $token,
        ])));
    }

    private function handleConnect(Request $request, string $provider, string $driver, array $stateData, string $callbackUrl): RedirectResponse
    {
        $connectToken = $stateData['connect_token'] ?? '';

        $pat = PersonalAccessToken::findToken($connectToken);
        if (! $pat) {
            return $this->redirectWithError('Session expired. Please try connecting again.');
        }

        /** @var User $user */
        $user = $pat->tokenable;

        if (! $user || ! $user->isCandidate()) {
            return $this->redirectWithError('Only candidate accounts can connect a LinkedIn profile.');
        }

        try {
            $oauthUser = Socialite::driver($driver)->stateless()->redirectUrl($callbackUrl)->user();
        } catch (Throwable $e) {
            return $this->redirectConnectError('Failed to authenticate with '.ucfirst($provider).'. Please try again.');
        }

        $this->fillMissingProfileData($user, $provider, $oauthUser);
        $this->syncAvatar($user, $oauthUser->getAvatar());

        return redirect()->away($this->frontendUrl('/oauth/callback?'.http_build_query([
            'mode'     => 'connect',
            'provider' => $provider,
        ])));
    }

    /**
     * Build candidate profile fields from the OAuth user data.
     * LinkedIn OpenID returns: sub, name, given_name, family_name, picture, email, locale.
     * It does NOT return a vanity URL, so we store the public profile search URL.
     */
    private function extractProfileData(string $provider, \Laravel\Socialite\Contracts\User $oauthUser): array
    {
        $data = [];

        if ($provider === 'linkedin') {
            $data['linkedin_url'] = 'https://www.linkedin.com/search/results/people/?keywords='
                .urlencode($oauthUser->getName() ?? '');

            $locale = $oauthUser->offsetExists('locale') ? $oauthUser->offsetGet('locale') : null;
            if (is_array($locale) && ! empty($locale['country'])) {
                $data['location'] = $locale['country'];
            }
        }

        return array_filter($data);
    }

    /**
     * Fill any empty profile fields for an existing candidate from OAuth data.
     */
    private function fillMissingProfileData(User $user, string $provider, \Laravel\Socialite\Contracts\User $oauthUser): void
    {
        $profile = $user->candidateProfile;
        if (! $profile) {
            return;
        }

        $updates = [];

        if ($provider === 'linkedin' && ! $profile->linkedin_url) {
            $updates['linkedin_url'] = 'https://www.linkedin.com/search/results/people/?keywords='
                .urlencode($oauthUser->getName() ?? '');
        }

        if (! $profile->location) {
            $locale = $oauthUser->offsetExists('locale') ? $oauthUser->offsetGet('locale') : null;
            if (is_array($locale) && ! empty($locale['country'])) {
                $updates['location'] = $locale['country'];
            }
        }

        if (! empty($updates)) {
            $profile->update($updates);
        }
    }

    /**
     * Download and store the provider avatar if the user has none.
     */
    private function syncAvatar(User $user, ?string $avatarUrl): void
    {
        if ($user->avatar || ! $avatarUrl) {
            return;
        }

        $contents = @file_get_contents($avatarUrl);
        if ($contents) {
            $path = 'avatars/'.Str::uuid().'.jpg';
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $contents);
            $user->forceFill(['avatar' => $path])->save();
        }
    }

    private function decodeState(?string $state): array
    {
        if (! $state) {
            return [];
        }
        $decoded = json_decode(base64_decode($state) ?: '', true);
        return is_array($decoded) ? $decoded : [];
    }

    private function redirectWithError(string $message): RedirectResponse
    {
        return redirect()->away($this->frontendUrl('/sign-in?'.http_build_query([
            'oauth_error' => $message,
        ])));
    }

    private function redirectConnectError(string $message): RedirectResponse
    {
        return redirect()->away($this->frontendUrl('/candidate/profile?'.http_build_query([
            'connect_error' => $message,
        ])));
    }

    private function frontendUrl(string $path): string
    {
        return rtrim((string) config('app.frontend_url'), '/').$path;
    }
}
