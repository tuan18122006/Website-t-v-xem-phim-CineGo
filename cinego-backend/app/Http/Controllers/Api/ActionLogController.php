<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use Illuminate\Http\Request;

class ActionLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActionLog::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('target_type', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
