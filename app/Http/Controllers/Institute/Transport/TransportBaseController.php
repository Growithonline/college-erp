<?php

namespace App\Http\Controllers\Institute\Transport;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class TransportBaseController extends Controller
{
    protected function instituteId(): int
    {
        $user = $this->authenticatedUser();
        abort_if(!$user || !$user->institute_id, 403, 'Institute context missing.');

        return (int) $user->institute_id;
    }

    // Transport routes accept web/staff/center/partner guards (see routes/web.php) — resolve
    // whichever one is actually logged in rather than relying only on the default 'web' guard.
    protected function authenticatedUser()
    {
        foreach (['staff', 'center', 'partner', 'web'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return auth()->guard($guard)->user();
            }
        }

        return auth()->user();
    }

    protected function assertInstituteModel(Model $model): void
    {
        abort_if((int) ($model->institute_id ?? 0) !== $this->instituteId(), 403);
    }

    protected function studentQuery(): Builder
    {
        return Student::with(['stream.course', 'coursePart', 'activeTransportAllocation.route', 'activeTransportAllocation.stop'])
            ->where('institute_id', $this->instituteId())
            ->orderBy('name');
    }

    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
