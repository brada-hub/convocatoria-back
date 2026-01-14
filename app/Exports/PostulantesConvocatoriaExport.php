<?php

namespace App\Exports;

use App\Models\Convocatoria;
use App\Models\Postulacion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PostulantesConvocatoriaExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected Convocatoria $convocatoria;
    protected ?int $sedeId;
    protected ?int $cargoId;
    protected ?string $estado;
    protected int $counter = 0;

    public function __construct(Convocatoria $convocatoria, ?int $sedeId = null, ?int $cargoId = null, ?string $estado = null)
    {
        $this->convocatoria = $convocatoria;
        $this->sedeId = $sedeId;
        $this->cargoId = $cargoId;
        $this->estado = $estado;
    }

    public function collection()
    {
        $query = Postulacion::whereIn('oferta_id', $this->convocatoria->ofertas()->pluck('id'))
            ->with([
                'postulante.formaciones',
                'postulante.experiencias',
                'postulante.capacitaciones',
                'postulante.producciones',
                'postulante.reconocimientos',
                'oferta.sede',
                'oferta.cargo'
            ]);

        // Aplicar filtros
        if ($this->sedeId) {
            $query->whereHas('oferta', fn($q) => $q->where('sede_id', $this->sedeId));
        }

        if ($this->cargoId) {
            $query->whereHas('oferta', fn($q) => $q->where('cargo_id', $this->cargoId));
        }

        if ($this->estado) {
            $query->where('estado', $this->estado);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'N°',
            'CI',
            'Nombres',
            'Apellidos',
            'Email',
            'Celular',
            'Fecha Nacimiento',
            'Género',
            'Sede Postulada',
            'Cargo Postulado',
            'Estado',
            'Fecha Postulación',
            // Formación Académica
            'Nivel Académico Máximo',
            'Título/Profesión',
            'Universidad',
            'Año Titulación',
            'Total Formaciones',
            // Experiencia
            'Experiencia Reciente (Cargo)',
            'Empresa/Institución',
            'Años de Experiencia',
            'Total Experiencias',
            // Otros méritos
            'Capacitaciones',
            'Producciones Intelectuales',
            'Reconocimientos',
            // Observaciones
            'Observaciones',
        ];
    }

    public function map($postulacion): array
    {
        $this->counter++;
        $postulante = $postulacion->postulante;

        // Si no hay postulante, devolver fila vacía
        if (!$postulante) {
            return array_fill(0, 25, '-');
        }

        $estadoTexto = match($postulacion->estado) {
            'pendiente' => 'Pendiente',
            'en_revision' => 'En Revisión',
            'observado' => 'Observado',
            'habilitado' => 'Habilitado',
            'rechazado' => 'Rechazado',
            'seleccionado' => 'Seleccionado',
            default => ucfirst($postulacion->estado ?? 'Pendiente')
        };

        // Obtener formación más alta
        $formaciones = $postulante->formaciones ?? collect();
        $formacionMasAlta = $formaciones->sortByDesc(function($f) {
            $orden = ['doctorado' => 5, 'maestria' => 4, 'especialidad' => 3, 'diplomado' => 2, 'licenciatura' => 1];
            return $orden[strtolower($f->nivel ?? '')] ?? 0;
        })->first();

        // Obtener experiencia más reciente
        $experiencias = $postulante->experiencias ?? collect();
        $experienciaReciente = $experiencias->sortByDesc('anio_inicio')->first();

        // Calcular años totales de experiencia
        $aniosExperiencia = 0;
        foreach ($experiencias as $exp) {
            $inicio = $exp->anio_inicio ?? 0;
            $fin = $exp->anio_fin ?? (int) date('Y');
            $aniosExperiencia += max(0, $fin - $inicio);
        }

        // Contar otros méritos
        $capacitaciones = $postulante->capacitaciones ?? collect();
        $producciones = $postulante->producciones ?? collect();
        $reconocimientos = $postulante->reconocimientos ?? collect();

        // Género legible
        $genero = match($postulante->genero ?? '') {
            'M' => 'Masculino',
            'F' => 'Femenino',
            default => '-'
        };

        // Fecha de nacimiento segura
        $fechaNacimiento = '-';
        if ($postulante->fecha_nacimiento) {
            try {
                $fechaNacimiento = \Carbon\Carbon::parse($postulante->fecha_nacimiento)->format('d/m/Y');
            } catch (\Exception $e) {
                $fechaNacimiento = '-';
            }
        }

        return [
            $this->counter,
            $postulante->ci ?? '-',
            $postulante->nombres ?? '-',
            $postulante->apellidos ?? '-',
            $postulante->email ?? '-',
            $postulante->celular ?? '-',
            $fechaNacimiento,
            $genero,
            $postulacion->oferta->sede->nombre ?? '-',
            $postulacion->oferta->cargo->nombre ?? '-',
            $estadoTexto,
            $postulacion->created_at ? $postulacion->created_at->format('d/m/Y H:i') : '-',
            // Formación
            $formacionMasAlta ? ucfirst($formacionMasAlta->nivel ?? 'Sin nivel') : 'Sin registro',
            $formacionMasAlta->titulo_profesion ?? '-',
            $formacionMasAlta->universidad ?? '-',
            $formacionMasAlta->anio_emision ?? '-',
            $formaciones->count(),
            // Experiencia
            $experienciaReciente->cargo_desempenado ?? 'Sin registro',
            $experienciaReciente->empresa_institucion ?? '-',
            $aniosExperiencia > 0 ? "{$aniosExperiencia} años" : 'Sin experiencia',
            $experiencias->count(),
            // Otros méritos
            $capacitaciones->count() > 0 ? "{$capacitaciones->count()} cursos" : 'Ninguno',
            $producciones->count() > 0 ? "{$producciones->count()} publicaciones" : 'Ninguno',
            $reconocimientos->count() > 0 ? "{$reconocimientos->count()} reconocimientos" : 'Ninguno',
            // Observaciones
            $postulacion->observaciones ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Obtener última fila con datos
        $lastRow = $sheet->getHighestRow();

        return [
            // Header row - Estilo morado
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '6B21A8'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
            // Todas las celdas
            "A1:AB{$lastRow}" => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Postulantes ' . substr($this->convocatoria->titulo, 0, 25);
    }
}
