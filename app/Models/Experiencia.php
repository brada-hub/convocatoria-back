<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Experiencia extends Model
{
    protected $fillable = ['postulante_id', 'tipo', 'cargo', 'institucion', 'fecha_inicio', 'fecha_fin', 'archivo_respaldo'];

    public function postulante() { return $this->belongsTo(Postulante::class); }
}
