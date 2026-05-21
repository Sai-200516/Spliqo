<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => null,
                'email_verified_at' => now(),
                'password'          => null,
            ]);
        }

        auth()->login($user, true);

        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }
}
