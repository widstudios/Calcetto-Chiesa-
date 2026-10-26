<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Tutti gli account con il loro ruolo: qui l'admin può nominare altri amministratori.
 * Un admin non può togliersi il ruolo né eliminarsi, così resta sempre almeno un amministratore.
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::orderByRaw('role = ? desc', [Role::Admin->value])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['role' => Role::Admin])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        User::create($this->withCoachFlag($data));

        return redirect()->route('users.index')->with('status', 'Utente creato: ora può accedere con la sua email.');
    }

    public function edit(User $user): View
    {
        return view('users.form', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $demoting = $user->isAdmin() && Role::from($data['role']) !== Role::Admin;
        if ($demoting && $user->is($request->user())) {
            return back()->withInput()->withErrors(['role' => 'Non puoi toglierti da solo il ruolo di amministratore: chiedilo a un altro admin.']);
        }

        $saved = DB::transaction(function () use ($user, $data, $demoting) {
            if ($demoting && $this->isLastAdmin($user)) {
                return false;
            }

            $user->update($this->withCoachFlag($data));
            // Chi non allena più non resta tra gli allenatori delle squadre.
            if (! $user->isCoach()) {
                $user->teams()->detach();
            }

            return true;
        });
        if (! $saved) {
            return back()->withInput()->withErrors(['role' => 'Deve restare almeno un amministratore.']);
        }

        return redirect()->route('users.index')->with('status', 'Utente aggiornato.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Non puoi eliminare il tuo account.']);
        }
        $deleted = DB::transaction(fn () => ! ($user->isAdmin() && $this->isLastAdmin($user)) && $user->delete());
        if (! $deleted) {
            return back()->withErrors(['user' => 'Deve restare almeno un amministratore.']);
        }

        return redirect()->route('users.index')->with('status', 'Account di '.$user->name.' eliminato.');
    }

    /**
     * Da chiamare dentro una transazione: blocca le righe degli admin, così due admin
     * che si tolgono il ruolo a vicenda nello stesso momento non lasciano l'app senza admin.
     */
    private function isLastAdmin(User $user): bool
    {
        return User::where('role', Role::Admin)->lockForUpdate()->pluck('id')->reject(fn ($id) => $id === $user->getKey())->isEmpty();
    }

    /**
     * Il flag serve solo agli admin: un allenatore lo è già per ruolo.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withCoachFlag(array $data): array
    {
        return [...$data, 'is_coach' => Role::from($data['role']) === Role::Admin && (bool) ($data['is_coach'] ?? false)];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            // Per un nuovo account la password serve: è il modo per accedere.
            'password' => [$user ? 'nullable' : 'required', 'string', Password::min(8)],
            'role' => ['required', new Enum(Role::class)],
            'is_coach' => ['boolean'],
        ]);
    }
}
