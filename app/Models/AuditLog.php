<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Obtener el modelo auditado
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Obtener el usuario que realizó la acción
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
