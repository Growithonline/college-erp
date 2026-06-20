<?php

namespace App\Http\Controllers\SuperAdmin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    //
    public function showLoginForm()
    {
        return view('super_admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::guard('super_admin')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('super_admin.dashboard');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Invalid credentials']);
    }

    public function logout()
    {
        Auth::guard('super_admin')->logout();
        return redirect()->route('super_admin.login');
    }
}
