<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reconocimiento extends Model
{
    protected $fillable = ['postulante_id', 'titulo_premio', 'anio', 'archivo_respaldo'];
}
