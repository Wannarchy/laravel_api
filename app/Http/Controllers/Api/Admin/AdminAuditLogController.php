<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminAuditLogResource;
use App\Models\AdminAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AdminAuditLog::with('admin')->orderByDesc('created_at');

        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->integer('admin_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->string('target_type'));
        }

        if ($request->filled('target_id')) {
            $query->where('target_id', $request->integer('target_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->string('date'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('action', 'like', $term)
                    ->orWhere('target_type', 'like', $term)
                    ->orWhere('ip', 'like', $term)
                    ->orWhereRaw('CAST(details AS TEXT) LIKE ?', [$term])
                    ->orWhereHas('admin', function ($adminQuery) use ($term) {
                        $adminQuery->where('email', 'like', $term)
                            ->orWhere('prenom', 'like', $term)
                            ->orWhere('nom', 'like', $term);
                    });
            });
        }

        $logs = $query->paginate($request->integer('per_page', 50));
        $logs->getCollection()->transform(
            fn (AdminAuditLog $log) => (new AdminAuditLogResource($log))->resolve()
        );

        return response()->json(['data' => $logs]);
    }
}
