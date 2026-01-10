<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentoPostulante extends Model
{
    use HasFactory;

    protected $table = 'documentos_postulante';

    protected $fillable = [
        'postulante_id',
        'tipo_documento_id',
        'archivo_pdf'
    ];

    // Relación con postulante
    public function postulante()
    {
        return $this->belongsTo(Postulante::class);
    }

    // Relación con tipo de documento
    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }
}
