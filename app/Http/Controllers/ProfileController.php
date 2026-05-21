<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'               => 'required|string|max:100',
            'email'              => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->_id, '_id')],
            'bio'                => 'nullable|string|max:500',
            'timezone'           => 'nullable|string|max:60',
            'preferred_currency' => 'nullable|string|size:3',
            'avatar'             => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        if ($data['email'] !== $user->email) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updateTheme(Request $request)
    {
        $request->validate(['theme' => 'required|in:light,dark,system']);
        $request->user()->update(['theme_preference' => $request->theme]);
        return response()->json(null, 204);
    }

    public function updateNotifications(Request $request)
    {
        $request->validate([
            'preferences'                 => 'required|array',
            'preferences.email'           => 'boolean',
            'preferences.push'            => 'boolean',
            'preferences.expense_added'   => 'boolean',
            'preferences.payment_settled' => 'boolean',
            'preferences.group_invite'    => 'boolean',
        ]);

        $request->user()->update(['notification_preferences' => $request->preferences]);

        return back()->with('success', 'Notification preferences saved.');
    }

    public function destroy(Request $request)
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        auth()->logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

