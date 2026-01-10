<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Postulacion extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'postulaciones';

    protected $fillable = ['postulante_id', 'oferta_id', 'estado', 'observaciones'];

    const ESTADOS = [
        'pendiente' => 'Pendiente',
        'en_revision' => 'En Revisión',
        'observado' => 'Observado',
        'habilitado' => 'Habilitado para Entrevista',
        'rechazado' => 'Rechazado',
        'seleccionado' => 'Seleccionado',
    ];

    public function postulante()
    {
        return $this->belongsTo(Postulante::class);
    }

    public function oferta()
    {
        return $this->belongsTo(Oferta::class, 'oferta_id');
    }

    // Acceso rápido a convocatoria, sede y cargo
    public function getConvocatoriaAttribute()
    {
        return $this->oferta->convocatoria ?? null;
    }

    public function getSedeAttribute()
    {
        return $this->oferta->sede ?? null;
    }

    public function getCargoAttribute()
    {
        return $this->oferta->cargo ?? null;
    }

    public function getEstadoTextoAttribute()
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
