<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Postulante extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'ci',
        'ci_expedido',
        'nombres',
        'apellidos',
        'email',
        'celular',
        'foto_perfil',
        'fecha_nacimiento',
        'genero',
        'nacionalidad',
        'direccion',
        'carta_postulacion_pdf',
        'curriculum_vitae_pdf',
        'ci_documento_pdf'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    // Accessor para nombre completo
    public function getNombreCompletoAttribute()
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    // Relaciones con tablas de méritos
    public function formaciones()
    {
        return $this->hasMany(Formacion::class);
    }

    public function experiencias()
    {
        return $this->hasMany(Experiencia::class);
    }

    public function capacitaciones()
    {
        return $this->hasMany(Capacitacion::class);
    }

    public function producciones()
    {
        return $this->hasMany(Produccion::class);
    }

    public function reconocimientos()
    {
        return $this->hasMany(Reconocimiento::class);
    }

    public function postulaciones()
    {
        return $this->hasMany(Postulacion::class);
    }

    // Documentos subidos por el postulante
    public function documentos()
    {
        return $this->hasMany(DocumentoPostulante::class);
    }

    // Obtener postulaciones con detalles de oferta
    public function postulacionesConDetalle()
    {
        return $this->postulaciones()
            ->with(['oferta.convocatoria', 'oferta.sede', 'oferta.cargo']);
    }
}
