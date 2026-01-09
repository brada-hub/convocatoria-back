<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postulacion extends Model
{
    protected $table = 'postulaciones';
    protected $fillable = ['postulante_id', 'convocatoria_id', 'estado'];

    public function postulante() { return $this->belongsTo(Postulante::class); }
    public function convocatoria() { return $this->belongsTo(Convocatoria::class); }
}
