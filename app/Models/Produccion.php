<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Produccion extends Model
{
    use HasFactory;

    protected $table = 'producciones';

    protected $fillable = [
        'postulante_id', 'tipo', 'titulo',
        'descripcion', 'anio', 'archivo_pdf'
    ];

    const TIPOS = [
        'libro' => 'Libro',
        'articulo' => 'Artículo Científico',
        'software' => 'Software',
        'investigacion' => 'Investigación',
        'proyecto' => 'Proyecto',
        'otro' => 'Otro',
    ];

    public function postulante()
    {
        return $this->belongsTo(Postulante::class);
    }

    public function getTipoTextoAttribute()
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
