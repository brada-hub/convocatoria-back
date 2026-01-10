<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login de administrador
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string', // Aceptamos CI o Email bajo el campo 'email'
            'password' => 'required',
        ]);

        // Buscar por email o por CI
        $user = User::where('email', $request->email)
            ->orWhere('ci', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // Crear token
        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'user' => [
                'id' => $user->id,
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'ci' => $user->ci,
                'email' => $user->email,
                'rol' => $user->rol,
                'must_change_password' => $user->must_change_password
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    /**
     * Usuario actual
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword(Request $request)
    {
        $user = $request->user();

        // Si el usuario debe cambiar contraseña obligatoriamente, no exigimos la actual (caso primer login CI)
        if ($user->must_change_password) {
            $request->validate([
                'password' => 'required|min:6|confirmed', // Mínimo 6 para coincidir con frontend
            ]);
        } else {
            // Caso normal: Cambio voluntario
            $request->validate([
                'current_password' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['La contraseña actual es incorrecta.'],
                ]);
            }
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false // Desactivar flag
        ]);

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente'
        ]);
    }
}
