<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Formacion extends Model
{
    use HasFactory;

    protected $table = 'formaciones';

    protected $fillable = [
        'postulante_id', 'nivel', 'titulo_profesion',
        'universidad', 'anio_emision', 'archivo_pdf'
    ];

    const NIVELES = [
        'licenciatura' => 'Licenciatura',
        'maestria' => 'Maestría',
        'doctorado' => 'Doctorado',
        'diplomado' => 'Diplomado',
        'especialidad' => 'Especialidad',
    ];

    public function postulante()
    {
        return $this->belongsTo(Postulante::class);
    }

    public function getNivelTextoAttribute()
    {
        return self::NIVELES[$this->nivel] ?? $this->nivel;
    }
}
