<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Capacitacion extends Model
{
    protected $table = 'capacitaciones';
    protected $fillable = ['postulante_id', 'nombre_curso', 'institucion', 'carga_horaria', 'archivo_respaldo'];
}
