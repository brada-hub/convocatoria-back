<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    /**
     * Boot the trait
     */
    public static function bootAuditable()
    {
        // Registrar cuando se crea
        static::created(function ($model) {
            $model->logAudit('created', null, $model->toArray());
        });

        // Registrar cuando se actualiza
        static::updated(function ($model) {
            $original = $model->getOriginal();
            $changes = $model->getChanges();

            // Solo auditar si hay cambios reales
            if (!empty($changes)) {
                $model->logAudit('updated', $original, $changes);
            }
        });

        // Registrar cuando se elimina
        static::deleted(function ($model) {
            $model->logAudit('deleted', $model->toArray(), null);
        });

        // Registrar cuando se restaura (soft delete)
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->logAudit('restored', null, $model->toArray());
            });
        }
    }

    /**
     * Registrar entrada de auditoría
     */
    protected function logAudit(string $event, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Obtener historial de auditoría del modelo
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
