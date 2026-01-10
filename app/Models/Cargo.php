<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cargo extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'requisitos', 'activo'];

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
            ->withPivot('sede_id', 'vacantes', 'activo')
            ->withTimestamps();
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
