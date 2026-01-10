<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\ConvocatoriaController;
use App\Http\Controllers\PostulacionController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema de Convocatorias
|--------------------------------------------------------------------------
*/

// ============================================
// RUTAS PÚBLICAS (Sin autenticación)
// ============================================

// Health check
Route::get('/status', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API de Convocatorias funcionando correctamente',
        'timestamp' => now()->toISOString(),
    ]);
});

// Convocatorias abiertas
Route::get('/convocatorias/abiertas', [ConvocatoriaController::class, 'abiertas']);
Route::get('/convocatorias/{slug}', [ConvocatoriaController::class, 'porSlug']);

// Verificación de CI
Route::post('/check-ci', [ConvocatoriaController::class, 'checkCI']);

// Consulta de estado de postulaciones
Route::post('/consultar-estado', [ConvocatoriaController::class, 'consultarEstado']);

// Proceso de postulación
Route::post('/postulante/registrar', [PostulacionController::class, 'registrarPostulante']);
Route::post('/postulante/expediente', [PostulacionController::class, 'guardarExpediente']);
Route::post('/postulante/postular', [PostulacionController::class, 'enviarPostulaciones']);
Route::post('/postulante/proceso-completo', [PostulacionController::class, 'procesoCompleto']);

// Catálogos públicos
Route::get('/sedes/activas', [SedeController::class, 'activas']);
Route::get('/cargos/activos', [CargoController::class, 'activos']);

// ============================================
// AUTENTICACIÓN
// ============================================
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/cambiar-password', [AuthController::class, 'cambiarPassword']);
});

// ============================================
// RUTAS ADMINISTRATIVAS (Requieren autenticación)
// ============================================
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    // ---- SEDES ----
    Route::get('/sedes', [SedeController::class, 'index']);
    Route::post('/sedes', [SedeController::class, 'store']);
    Route::get('/sedes/{sede}', [SedeController::class, 'show']);
    Route::put('/sedes/{sede}', [SedeController::class, 'update']);
    Route::delete('/sedes/{sede}', [SedeController::class, 'destroy']);
    Route::patch('/sedes/{sede}/toggle', [SedeController::class, 'toggleActivo']);

    // ---- CARGOS ----
    Route::get('/cargos', [CargoController::class, 'index']);
    Route::post('/cargos', [CargoController::class, 'store']);
    Route::get('/cargos/{cargo}', [CargoController::class, 'show']);
    Route::put('/cargos/{cargo}', [CargoController::class, 'update']);
    Route::delete('/cargos/{cargo}', [CargoController::class, 'destroy']);
    Route::patch('/cargos/{cargo}/toggle', [CargoController::class, 'toggleActivo']);

    // ---- CONVOCATORIAS ----
    Route::get('/convocatorias', [ConvocatoriaController::class, 'index']);
    Route::post('/convocatorias', [ConvocatoriaController::class, 'store']);
    Route::get('/convocatorias/{convocatoria}', [ConvocatoriaController::class, 'show']);
    Route::put('/convocatorias/{convocatoria}', [ConvocatoriaController::class, 'update']);
    Route::delete('/convocatorias/{convocatoria}', [ConvocatoriaController::class, 'destroy']);

    // Ofertas dentro de convocatoria
    Route::post('/convocatorias/{convocatoria}/ofertas', [ConvocatoriaController::class, 'agregarOferta']);
    Route::delete('/convocatorias/{convocatoria}/ofertas/{oferta}', [ConvocatoriaController::class, 'eliminarOferta']);

    // Postulaciones y expedientes
    Route::get('/convocatorias/{convocatoria}/postulaciones', [ConvocatoriaController::class, 'postulaciones']);
    Route::get('/convocatorias/{convocatoria}/estadisticas', [ConvocatoriaController::class, 'estadisticas']);

    // Gestión de postulaciones
    Route::get('/postulaciones', [ConvocatoriaController::class, 'listarPostulaciones']);
    Route::patch('/postulaciones/{postulacion}/estado', [ConvocatoriaController::class, 'cambiarEstadoPostulacion']);

    // Ver expediente de postulante
    Route::get('/postulantes/{postulante}/expediente', [ConvocatoriaController::class, 'verExpediente']);

    // Dashboard stats
    Route::get('/dashboard/stats', function () {
        return response()->json([
            'convocatorias_activas' => \App\Models\Convocatoria::abiertas()->count(),
            'total_postulaciones' => \App\Models\Postulacion::count(),
            'pendientes' => \App\Models\Postulacion::where('estado', 'pendiente')->count(),
            'postulantes' => \App\Models\Postulante::count(),
            'recientes' => \App\Models\Postulacion::with(['postulante', 'oferta.cargo', 'oferta.convocatoria'])
                            ->orderBy('created_at', 'desc')
                            ->take(5)
                            ->get(),
            'proximas_cierre' => \App\Models\Convocatoria::where('estado', 'activa')
                                    ->whereDate('fecha_cierre', '>=', now())
                                    ->orderBy('fecha_cierre', 'asc')
                                    ->take(4)
                                    ->get()
        ]);
    });
});
