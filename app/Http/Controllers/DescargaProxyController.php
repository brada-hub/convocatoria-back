<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DescargaProxyController extends Controller
{
    public function descargar(Request $request)
    {
        $path = $request->query('path');
        if (!$path) {
            return response()->json(['error' => 'Path requerido'], 400);
        }

        // 1. Normalizar separadores para el sistema operativo (Windows usa \)
        $cleanPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        // 2. Construir ruta absoluta base: storage/app/public
        $root = storage_path('app' . DIRECTORY_SEPARATOR . 'public');

        // 3. Construir ruta completa
        $fullPath = $root . DIRECTORY_SEPARATOR . $cleanPath;

        // 4. Verificar existencia física (Validado con script de debug)
        if (file_exists($fullPath)) {
            return response()->file($fullPath);
        }

        // 5. Fallback: Intentar limpiar prefijo 'storage' si viene incluido
        // Ejemplo: "storage/documentos/..." -> "documentos/..."
        $cleanPathNoStorage = str_replace('storage' . DIRECTORY_SEPARATOR, '', $cleanPath);
        $fullPath2 = $root . DIRECTORY_SEPARATOR . $cleanPathNoStorage;

        if (file_exists($fullPath2)) {
             return response()->file($fullPath2);
        }

        Log::error("Documento Proxy 404. Buscado en:\n1: $fullPath\n2: $fullPath2");

        return response()->json([
            'message' => 'Archivo no encontrado',
            'debug_path_1' => $fullPath,
            'debug_path_2' => $fullPath2
        ], 404);
    }
}
