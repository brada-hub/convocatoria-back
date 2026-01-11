<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NivelAcademico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'niveles_academicos';

    protected $fillable = [
        'nombre',
        'slug',
        'activo'
    ];
}
