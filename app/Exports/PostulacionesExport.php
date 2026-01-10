<?php

namespace App\Exports;

use App\Models\Postulacion;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PostulacionesExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Postulacion::query()
            ->with(['postulante', 'oferta.cargo', 'oferta.sede', 'oferta.convocatoria']);

        if (!empty($this->filters['convocatoria_id'])) {
            $query->whereHas('oferta', function ($q) {
                $q->where('convocatoria_id', $this->filters['convocatoria_id']);
            });
        }

        if (!empty($this->filters['estado'])) {
            $query->where('estado', $this->filters['estado']);
        }

        if (!empty($this->filters['sede_id'])) {
            $query->whereHas('oferta', function ($q) {
                $q->where('sede_id', $this->filters['sede_id']);
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'CI',
            'Nombres',
            'Apellidos',
            'Email',
            'Celular',
            'Convocatoria',
            'Sede',
            'Cargo',
            'Estado',
            'Fecha Postulación',
        ];
    }

    public function map($postulacion): array
    {
        return [
            $postulacion->id,
            $postulacion->postulante?->ci,
            $postulacion->postulante?->nombres,
            $postulacion->postulante?->apellidos,
            $postulacion->postulante?->email,
            $postulacion->postulante?->celular,
            $postulacion->oferta?->convocatoria?->titulo,
            $postulacion->oferta?->sede?->nombre,
            $postulacion->oferta?->cargo?->nombre,
            ucfirst($postulacion->estado),
            $postulacion->created_at?->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FF4F46E5']
                ],
                'font' => ['color' => ['argb' => 'FFFFFFFF'], 'bold' => true]
            ],
        ];
    }
}
