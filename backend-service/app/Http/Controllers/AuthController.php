<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class AuthController extends Controller
{
    public function showSigninForm()
    {
        return view('auth.signin');
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::where('google_id', $googleUser->id)->where('email', $googleUser->email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
            ]);
        }

        Auth::login($user);

        $authToken = $user->createToken('auth_token')->plainTextToken;

        return redirect()->route('dashboard')->with('auth_token', $authToken);
    }

    public function redirectToMicrosoft()
    {
        return Socialite::driver('microsoft')->redirect();
    }

    public function handleMicrosoftCallback()
    {
        $microsoftUser = Socialite::driver('microsoft')->user();

        $user = User::where('microsoft_id', $microsoftUser->id)->where('email', $microsoftUser->email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $microsoftUser->name,
                'email' => $microsoftUser->email,
                'microsoft_id' => $microsoftUser->id,
            ]);
        }

        Auth::login($user);

        $authToken = $user->createToken('auth_token')->plainTextToken;

        return redirect()->route('dashboard')->with('auth_token', $authToken);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('logged_out', true);
    }
}
