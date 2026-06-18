<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function pageView(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['required', 'string', 'max:120'],
        ]);

        $page = basename(trim($validated['page']));

        if (! AuditLogger::isAllowedPageView($page)) {
            return response()->json(['ok' => true]);
        }

        AuditLogger::logPageView($request, $page);

        return response()->json(['ok' => true]);
    }
}
