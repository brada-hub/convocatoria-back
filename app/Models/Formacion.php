<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formacion extends Model
{
    protected $table = 'formaciones';
    protected $fillable = ['postulante_id', 'tipo', 'titulo', 'universidad', 'anio_emision', 'archivo_respaldo'];
}
