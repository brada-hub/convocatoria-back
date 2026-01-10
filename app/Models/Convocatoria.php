<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\Auditable;

class Convocatoria extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_cierre',
        'slug',
        'estado'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_cierre' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($convocatoria) {
            if (empty($convocatoria->slug)) {
                $convocatoria->slug = Str::slug($convocatoria->titulo . '-' . Str::random(5));
            }
        });
    }

    public function ofertas()
    {
        return $this->hasMany(Oferta::class);
    }

    public function sedes()
    {
        return $this->belongsToMany(Sede::class, 'convocatoria_sede_cargo')
            ->withPivot('cargo_id', 'vacantes', 'activo')
            ->withTimestamps();
    }

    public function cargos()
    {
        return $this->belongsToMany(Cargo::class, 'convocatoria_sede_cargo')
            ->withPivot('sede_id', 'vacantes', 'activo')
            ->withTimestamps();
    }

    public function postulaciones()
    {
        return $this->hasManyThrough(Postulacion::class, Oferta::class, 'convocatoria_id', 'oferta_id');
    }

    // Documentos requeridos para esta convocatoria
    public function documentosRequeridos()
    {
        return $this->belongsToMany(TipoDocumento::class, 'convocatoria_documentos', 'convocatoria_id', 'tipo_documento_id')
            ->withPivot('obligatorio', 'orden')
            ->orderByPivot('orden')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa')
            ->where('fecha_cierre', '>=', now());
    }

    public function scopeAbiertas($query)
    {
        return $query->where('fecha_inicio', '<=', now())
            ->where('fecha_cierre', '>=', now());
    }

    // Helper para verificar si está activa (solo basado en fechas)
    public function getEstaAbiertaAttribute()
    {
        return $this->fecha_inicio <= now()
            && $this->fecha_cierre >= now();
    }
}
