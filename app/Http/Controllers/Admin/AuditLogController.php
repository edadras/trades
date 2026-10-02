<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate(['action' => ['nullable', 'string', 'max:80'], 'user' => ['nullable', 'integer']]);
        $logs = AuditLog::with('user:id,name,email')
            ->when($filters['action'] ?? null, fn ($q, $a) => $q->where('action', 'like', "{$a}%"))
            ->when($filters['user'] ?? null, fn ($q, $u) => $q->where('user_id', $u))
            ->latest('id')->paginate(50)->withQueryString()
            ->through(fn (AuditLog $l) => [
                'id' => $l->id, 'action' => $l->action, 'user' => $l->user?->only(['id', 'name', 'email']),
                'subject' => $l->subject_type ? $l->subject_type.'#'.$l->subject_id : null, 'properties' => $l->properties,
                'ip' => $l->ip_address, 'created_at' => $l->created_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/AuditLogs', ['logs' => $logs, 'filters' => $filters]);
    }
}
