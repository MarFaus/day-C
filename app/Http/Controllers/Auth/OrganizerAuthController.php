<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OrganizerAuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('organizer_id')) {
            return redirect()->route('admin.events.index');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $organizer = Organizer::query()->where('email', $credentials['email'])->first();

        if (! $organizer || ! $organizer->password_hash || ! Hash::check($credentials['password'], $organizer->password_hash)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email or password not correct']);
        }

        $request->session()->regenerate();
        $request->session()->put('organizer_id', $organizer->id);

        return redirect()->intended(route('admin.events.index'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget('organizer_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
