<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImageEncoder;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('error', 'Google sign-in failed. Please try again.');
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $updates = [];
            if (!$user->google_id) {
                $updates['google_id'] = $googleUser->getId();
            }
            // Only set avatar from Google if the user has none yet
            if (!$user->avatar && $googleUser->getAvatar()) {
                $encoded = app(ImageEncoder::class)->encodeUrl($googleUser->getAvatar(), 400);
                if ($encoded) {
                    $updates['avatar'] = $encoded;
                }
            }
            if ($updates) {
                $user->update($updates);
            }
        } else {
            $googleAvatar = $googleUser->getAvatar()
                ? app(ImageEncoder::class)->encodeUrl($googleUser->getAvatar(), 400)
                : null;

            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleAvatar,
                'email_verified_at' => now(),
                'password'          => null,
            ]);
        }

        auth()->login($user, true);

        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }
}
