<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Read-only, owner-only audit browser. */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), AuditLogger::RESOURCE_TYPES, true) ? $request->query('type') : null;
        $actor = $request->integer('actor') ?: null;
        $action = preg_match('/^[a-z_.]{1,80}$/', (string) $request->query('action')) ? $request->query('action') : null;

        return view('admin.audit.index', [
            'logs' => AuditLog::with('actor:id,name')
                ->when($type, fn ($q, $t) => $q->where('resource_type', $t))
                ->when($actor, fn ($q, $a) => $q->where('actor_id', $a))
                ->when($action, fn ($q, $a) => $q->where('action', 'like', $a.'%'))
                ->latest('id')->paginate(50)->withQueryString(),
            'types' => AuditLogger::RESOURCE_TYPES,
            'actors' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
