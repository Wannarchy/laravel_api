<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LogResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with(['user'])->orderByDesc('created_at');

        if ($request->filled('actor_type')) {
            $actorType = $request->string('actor_type')->toString();
            if (in_array($actorType, [ActivityLog::ACTOR_ADMIN, ActivityLog::ACTOR_USER, ActivityLog::ACTOR_GUEST], true)) {
                $query->where('actor_type', $actorType);
            }
        }

        if ($request->filled('admin_id')) {
            $query->where('user_id', $request->integer('admin_id'))
                ->where('actor_type', ActivityLog::ACTOR_ADMIN);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
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
                    ->orWhereHas('user', function ($userQuery) use ($term) {
                        $userQuery->where('email', 'like', $term)
                            ->orWhere('prenom', 'like', $term)
                            ->orWhere('nom', 'like', $term);
                    });
            });
        }

        $logs = $query->paginate($request->integer('per_page', 50));
        $logs->getCollection()->transform(
            fn (ActivityLog $log) => (new LogResource($log))->resolve()
        );

        return response()->json(['data' => $logs]);
    }
}
