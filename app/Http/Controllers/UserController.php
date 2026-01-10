<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return User::with('rol')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'ci' => 'required|string|unique:users,ci',
            'rol_id' => 'required|exists:rols,id',
            'email' => 'nullable|email|unique:users,email',
        ]);

        // Default password is CI
        $validated['password'] = Hash::make($validated['ci']);
        $validated['must_change_password'] = true;
        $validated['activo'] = true;

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return $user->load('rol');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'ci' => 'required|string|unique:users,ci,' . $user->id,
            'rol_id' => 'required|exists:rols,id',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'activo' => 'boolean',
        ]);

        if (empty($validated['email'])) {
            unset($validated['email']); // Don't overwrite if not provided and nullable
        }

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }

    public function resetPassword(User $user)
    {
        $user->password = Hash::make($user->ci);
        $user->must_change_password = true;
        $user->save();

        return response()->json(['message' => 'Contraseña restablecida correctamente al CI del usuario.']);
    }

    public function toggleActivo(User $user)
    {
        $user->activo = !$user->activo;
        $user->save();
        return response()->json($user);
    }
}
