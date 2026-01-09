<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\ConvocatoriaController;
use App\Http\Controllers\PostulacionController;

Route::get('/mensaje', function () {
    return response()->json([
        'message' => '¡Hola desde el backend de Laravel!',
        'status' => 'success'
    ]);
});

// Rutas Públicas de Postulación
Route::post('/check-ci', [ConvocatoriaController::class, 'checkCI']);
Route::post('/postular', [PostulacionController::class, 'store']);

// Rutas Administrativas
Route::get('/admin/postulaciones', [ConvocatoriaController::class, 'indexPostulaciones']);
Route::post('/admin/convocatorias', [ConvocatoriaController::class, 'store']);

