<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ChatLog::with('user')
            ->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        $logs = $query->paginate($request->integer('per_page', 50));

        return response()->json(['data' => $logs]);
    }
}
