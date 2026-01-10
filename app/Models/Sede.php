<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Sede extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = ['nombre', 'direccion', 'ciudad', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function ofertas()
    {
        return $this->hasMany(Oferta::class);
    }

    public function convocatorias()
    {
        return $this->belongsToMany(Convocatoria::class, 'convocatoria_sede_cargo')
            ->withPivot('cargo_id', 'vacantes', 'activo')
            ->withTimestamps();
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
