<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    public static function log(
        ?int $instituteId,
        string $module,
        string $action,
        ?string $description = null,
        mixed $auditable = null,
        array $meta = []
    ): void {
        try {
            [$actorType, $actorId] = self::resolveActor();

            AuditLog::create([
                'institute_id' => $instituteId,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'module' => $module,
                'action' => $action,
                'auditable_type' => $auditable ? get_class($auditable) : null,
                'auditable_id' => $auditable?->id,
                'description' => $description,
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function resolveActor(): array
    {
        foreach (['staff', 'center', 'partner', 'web'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return [$guard, auth()->guard($guard)->id()];
            }
        }

        return [null, null];
    }
}
