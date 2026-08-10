<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Center;
use App\Models\ChannelPartner;
use App\Models\GroupAdmin;
use App\Models\Institute;
use App\Models\StaffMember;
use App\Models\SuperAdmin;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'module'     => 'nullable|string|max:60',
            'actor_type' => 'nullable|string|max:30',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = AuditLog::latest('created_at');

        if ($request->filled('module'))     $query->where('module', $request->module);
        if ($request->filled('actor_type')) $query->where('actor_type', $request->actor_type);
        if ($request->filled('date_from'))  $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))    $query->whereDate('created_at', '<=', $request->date_to);

        $logs = $query->paginate(30)->withQueryString();

        $logs->getCollection()->transform(function (AuditLog $log) {
            $log->actor_name = $this->resolveActorName($log->actor_type, $log->actor_id);
            $log->institute_name = $log->institute_id ? Institute::find($log->institute_id)?->name : null;
            return $log;
        });

        $modules = AuditLog::whereNotNull('module')->distinct()->orderBy('module')->pluck('module');
        $actorTypes = AuditLog::whereNotNull('actor_type')->distinct()->orderBy('actor_type')->pluck('actor_type');

        return view('super_admin.audit_logs.index', compact('logs', 'modules', 'actorTypes'));
    }

    private function resolveActorName(?string $type, ?int $id): string
    {
        if (!$type || !$id) {
            return 'System';
        }

        $name = match ($type) {
            'super_admin'  => SuperAdmin::find($id)?->name,
            'group_admin'  => GroupAdmin::find($id)?->name,
            'staff'        => StaffMember::find($id)?->name,
            'center'       => Center::find($id)?->name,
            'partner'      => ChannelPartner::find($id)?->name,
            'web'          => User::find($id)?->name,
            default        => null,
        };

        return $name ?? ucfirst(str_replace('_', ' ', $type)) . " #{$id}";
    }
}
