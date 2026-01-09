<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postulante extends Model
{
    protected $fillable = ['ci', 'nombre_completo', 'email', 'telefono'];

    public function formaciones() { return $this->hasMany(Formacion::class); }
    public function experiencias() { return $this->hasMany(Experiencia::class); }
    public function capacitaciones() { return $this->hasMany(Capacitacion::class); }
    public function reconocimientos() { return $this->hasMany(Reconocimiento::class); }
    public function postulaciones() { return $this->hasMany(Postulacion::class); }
}
