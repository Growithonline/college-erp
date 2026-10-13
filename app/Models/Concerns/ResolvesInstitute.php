<?php

namespace App\Models\Concerns;

/**
 * Resolves the institute an account belongs to, from whichever column/relation carries it.
 * Unknown institute => null => treated as "not a member" by scoped login.
 */
trait ResolvesInstitute
{
    public function resolveInstituteId(): ?int
    {
        $id = $this->getAttribute('institute_id');

        if (blank($id)) {
            foreach (['franchise', 'profile'] as $relation) {
                if (method_exists($this, $relation) && ($related = $this->{$relation}) && filled($related->institute_id ?? null)) {
                    $id = $related->institute_id;
                    break;
                }
            }
        }

        return filled($id) ? (int) $id : null;
    }
}
