<?php

namespace App\Http\Controllers;

use App\Models\Professor;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $professors = Professor::where('is_active', true)->orderBy('name')->get();
        $transactions = $user->walletTransactions()->with('professor')->latest()->paginate(15);

        return view('inbox.wallet', compact('user', 'professors', 'transactions'));
    }

    public function redeem(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'professor_id' => ['required', Rule::exists('professors', 'id')->where('is_active', true)],
            'points' => ['required', 'integer', 'min:1'],
        ]);

        $professor = DB::transaction(function () use ($request, $user) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->wallet_balance < (int) $request->points) {
                throw ValidationException::withMessages([
                    'points' => 'Insufficient wallet balance.',
                ]);
            }

            $professor = Professor::findOrFail($request->professor_id);

            $lockedUser->decrement('wallet_balance', (int) $request->points);

            WalletTransaction::create([
                'user_id' => $lockedUser->id,
                'professor_id' => $professor->id,
                'type' => 'prof_redemption',
                'amount' => -((int) $request->points),
                'description' => "Redeemed for {$professor->name}",
            ]);

            return $professor;
        });

        return redirect()->back()->with(
            'success',
            "Successfully redeemed {$request->points} pt(s) for {$professor->name}.",
        );
    }
}
