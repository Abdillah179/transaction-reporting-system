<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login()
    {
        return view('Auth.Login', [
            'judul' => 'Login',
        ]);
    }

    public function PostLogin(Request $request)
    {
        $validation = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(['email' => $validation['email'], 'password' => $validation['password']], $request->boolean('remember'))) {

            $user = auth()->user();

            if (empty($user->email_verified_at)) {

                Auth::logout();

                $request->session()->invalidate();

                $request->session()->regenerateToken();

                return redirect('/');
            }

            $request->session()->regenerate();

            return redirect()->intended('/Dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function Logout(Request $request)
    {

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
