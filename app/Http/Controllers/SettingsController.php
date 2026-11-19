<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.edit', ['user' => $request->user()]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $email = $request->user()->email;
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Password reset link sent to '.$email);
        }

        return back()->with('error', 'Unable to send reset link right now. Please try again.');
    }
}
