<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Ogni utente (admin o allenatore) cambia la propria password.
 */
class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('account.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ], [
            'current_password.required' => 'Scrivi la password attuale.',
            'current_password.current_password' => 'La password attuale non è corretta.',
            'password.required' => 'Scrivi la nuova password.',
            'password.min' => 'La nuova password deve avere almeno 8 caratteri.',
            'password.confirmed' => 'Le due nuove password non coincidono.',
        ]);

        $user = $request->user();
        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();

        // Chi aveva la vecchia password viene scollegato dagli altri dispositivi.
        $request->session()->regenerate();
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        return redirect()->route('dashboard')->with('status', 'Password cambiata.');
    }
}
