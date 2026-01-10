<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoDocumento extends Model
{
    use HasFactory;

    protected $table = 'tipos_documento';

    protected $fillable = [
        'nombre',
        'descripcion',
        'icono',
        'activo',
        'orden'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Convocatorias que requieren este documento
    public function convocatorias()
    {
        return $this->belongsToMany(Convocatoria::class, 'convocatoria_documentos')
            ->withPivot('obligatorio', 'orden')
            ->withTimestamps();
    }

    // Documentos subidos por postulantes
    public function documentosPostulante()
    {
        return $this->hasMany(DocumentoPostulante::class);
    }

    // Scope para activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
