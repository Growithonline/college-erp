<?php

namespace App\Http\Controllers\GroupAdmin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('group_admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::guard('group_admin')->attempt($credentials)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid credentials']);
        }

        $groupAdmin = Auth::guard('group_admin')->user()->load('group');

        if (!$groupAdmin->status || !$groupAdmin->group?->status) {
            Auth::guard('group_admin')->logout();
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Your account has been disabled.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('group_admin.dashboard'));
    }

    public function logout()
    {
        Auth::guard('group_admin')->logout();
        return redirect()->route('group_admin.login');
    }
}
