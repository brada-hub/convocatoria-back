<?php

namespace App\Http\Controllers;

use App\Models\Postulacion;
use App\Models\Postulante;
use App\Models\Convocatoria;
use App\Exports\PostulacionesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    /**
     * Obtener datos para el dashboard de reportes
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'all');
        $startDate = $this->getStartDate($period);

        // KPIs básicos
        $totalPostulaciones = Postulacion::when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))->count();
        $postulantesUnicos = Postulacion::when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->distinct('postulante_id')->count('postulante_id');
        $convocatoriasActivas = Convocatoria::abiertas()->count();

        $habilitados = Postulacion::where('estado', 'habilitado')
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))->count();
        $tasaHabilitacion = $totalPostulaciones > 0 ? round(($habilitados / $totalPostulaciones) * 100, 1) : 0;

        // Postulaciones por estado
        $porEstado = Postulacion::select('estado', DB::raw('count(*) as total'))
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        // Postulaciones por mes (últimos 6 meses)
        $porMes = Postulacion::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as mes"),
            DB::raw('count(*) as total')
        )
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Top 5 sedes
        $topSedes = DB::table('postulaciones')
            ->join('convocatoria_sede_cargo', 'postulaciones.oferta_id', '=', 'convocatoria_sede_cargo.id')
            ->join('sedes', 'convocatoria_sede_cargo.sede_id', '=', 'sedes.id')
            ->select('sedes.nombre', DB::raw('count(*) as total'))
            ->when($startDate, fn($q) => $q->where('postulaciones.created_at', '>=', $startDate))
            ->groupBy('sedes.id', 'sedes.nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Top 5 cargos
        $topCargos = DB::table('postulaciones')
            ->join('convocatoria_sede_cargo', 'postulaciones.oferta_id', '=', 'convocatoria_sede_cargo.id')
            ->join('cargos', 'convocatoria_sede_cargo.cargo_id', '=', 'cargos.id')
            ->select('cargos.nombre', DB::raw('count(*) as total'))
            ->when($startDate, fn($q) => $q->where('postulaciones.created_at', '>=', $startDate))
            ->groupBy('cargos.id', 'cargos.nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Convocatorias activas con días restantes
        $convocatorias = Convocatoria::abiertas()
            ->withCount('postulaciones')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'titulo' => $c->titulo,
                'postulaciones' => $c->postulaciones_count,
                'dias_restantes' => Carbon::parse($c->fecha_cierre)->diffInDays(now())
            ]);

        // Últimas 10 postulaciones
        $ultimasPostulaciones = Postulacion::with(['postulante', 'oferta.cargo', 'oferta.sede'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_postulaciones' => $totalPostulaciones,
            'postulantes_unicos' => $postulantesUnicos,
            'convocatorias_activas' => $convocatoriasActivas,
            'tasa_habilitacion' => $tasaHabilitacion,
            'por_estado' => [
                'labels' => $porEstado->keys()->toArray(),
                'data' => $porEstado->values()->toArray()
            ],
            'por_mes' => [
                'labels' => $porMes->pluck('mes')->map(fn($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'))->toArray(),
                'data' => $porMes->pluck('total')->toArray()
            ],
            'top_sedes' => $topSedes,
            'top_cargos' => $topCargos,
            'convocatorias' => $convocatorias,
            'ultimas_postulaciones' => $ultimasPostulaciones
        ]);
    }

    /**
     * Exportar postulaciones a Excel
     */
    public function exportar(Request $request)
    {
        $filters = $request->only(['convocatoria_id', 'estado', 'sede_id']);
        $filename = 'postulaciones_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new PostulacionesExport($filters), $filename);
    }

    /**
     * Determinar fecha de inicio según período
     */
    private function getStartDate($period)
    {
        return match ($period) {
            '30' => Carbon::now()->subDays(30),
            '90' => Carbon::now()->subDays(90),
            '365' => Carbon::now()->subYear(),
            default => null
        };
    }
}
