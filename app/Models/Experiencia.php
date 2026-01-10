<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Experiencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'postulante_id', 'cargo_desempenado', 'empresa_institucion',
        'anio_inicio', 'anio_fin', 'funciones', 'archivo_pdf'
    ];

    public function postulante()
    {
        return $this->belongsTo(Postulante::class);
    }

    // Validar que la experiencia sea de los últimos 5 años
    public function getEsValidaAttribute()
    {
        $anioLimite = date('Y') - 5;
        $anioFin = $this->anio_fin ?? date('Y');

        return $anioFin >= $anioLimite;
    }
}
