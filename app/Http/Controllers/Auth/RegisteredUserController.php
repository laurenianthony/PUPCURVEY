<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'student_id' => ['required', 'string', 'unique:users,student_id', 'regex:/^\d{4}-\d{5}-CL-\d$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'program' => ['required', 'string', 'in:BSIT,BTLED,BSENT'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'student_id' => strtoupper($request->student_id),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'program' => $request->program,
            'year_level' => $request->year_level,
            'role' => 'user',
            'status' => 'pending',
            'wallet_balance' => 0,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.pending');
    }
}
