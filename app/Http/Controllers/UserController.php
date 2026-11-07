<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with('roles')->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        return view('users.index', [
            'users' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('users.form', [
            'user' => new User(['is_active' => true]),
            'roles' => Role::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(array_keys(Role::options()))],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'is_active' => $request->boolean('is_active'),
        ]);
        $user->syncRoles([$data['role']]);
        AuditLogger::record('user.created', $user, [], $user->only(['name', 'email', 'is_active']), 'Usuario creado');

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        return view('users.form', [
            'user' => $user->load('roles'),
            'roles' => Role::options(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(array_keys(Role::options()))],
        ]);

        if ($request->user()->is($user) && ! $request->boolean('is_active')) {
            return back()->withInput()->with('error', 'No puedes desactivar tu propio usuario.');
        }

        $old = $user->only(['name', 'email', 'is_active']);
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => $data['password']]);
        }

        $user->syncRoles([$data['role']]);
        AuditLogger::record('user.updated', $user, $old, $user->only(['name', 'email', 'is_active']), 'Usuario actualizado');

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $user->update(['is_active' => false]);
        $user->delete();
        AuditLogger::record('user.deleted', $user, $user->only(['name', 'email']), [], 'Usuario desactivado');

        return redirect()->route('users.index')->with('success', 'Usuario desactivado correctamente.');
    }
}
