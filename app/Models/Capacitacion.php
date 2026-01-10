<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Capacitacion extends Model
{
    use HasFactory;

    protected $table = 'capacitaciones';

    protected $fillable = [
        'postulante_id', 'nombre_curso', 'institucion_emisora',
        'carga_horaria', 'anio', 'archivo_pdf'
    ];

    public function postulante()
    {
        return $this->belongsTo(Postulante::class);
    }
}
