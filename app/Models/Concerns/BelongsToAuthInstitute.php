<?php

namespace App\Models\Concerns;

/**
 * Forces institute_id to the currently authenticated user's institute on create,
 * regardless of what a caller passed in — closes the mass-assignment risk of
 * institute_id being $fillable without having to rewrite every ::create() call.
 *
 * A no-op when no guard is authenticated (console/queue/seeder context), so
 * explicitly-passed institute_id still works there.
 */
trait BelongsToAuthInstitute
{
    protected static function bootBelongsToAuthInstitute(): void
    {
        static::creating(function ($model) {
            $instituteId = self::resolveAuthInstituteId();

            if ($instituteId !== null) {
                $model->institute_id = $instituteId;
            }
        });
    }

    private static function resolveAuthInstituteId(): ?int
    {
        foreach (['staff', 'center', 'partner', 'web'] as $guard) {
            $user = auth()->guard($guard)->user();
            if ($user && !empty($user->institute_id)) {
                return (int) $user->institute_id;
            }
        }

        return null;
    }
}
