<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reconocimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'postulante_id', 'tipo_reconocimiento', 'titulo',
        'otorgado_por', 'anio', 'archivo_pdf'
    ];

    public function postulante()
    {
        return $this->belongsTo(Postulante::class);
    }
}
