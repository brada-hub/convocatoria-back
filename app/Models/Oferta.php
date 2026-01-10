<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Oferta extends Model
{
    use HasFactory;

    protected $table = 'convocatoria_sede_cargo';

    protected $fillable = ['convocatoria_id', 'sede_id', 'cargo_id', 'vacantes', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
        'vacantes' => 'integer',
    ];

    public function convocatoria()
    {
        return $this->belongsTo(Convocatoria::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    public function postulaciones()
    {
        return $this->hasMany(Postulacion::class, 'oferta_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Obtener número de postulantes para esta oferta
    public function getCantidadPostulantesAttribute()
    {
        return $this->postulaciones()->count();
    }
}
