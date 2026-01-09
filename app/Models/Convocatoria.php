<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Convocatoria extends Model
{
    protected $fillable = ['titulo', 'sede', 'carrera', 'fecha_inicio', 'fecha_fin', 'slug'];

    public function postulaciones()
    {
        return $this->hasMany(Postulacion::class);
    }
}
