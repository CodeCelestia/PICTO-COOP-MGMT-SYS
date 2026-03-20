<?php

namespace App\Http\Controllers;

use App\Models\LoginSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SessionHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = LoginSession::with('user:id,name,email')
            ->orderBy('login_at', 'desc');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('ip_address', 'like', "%{$search}%");
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('login_status', $request->status);
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('login_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('login_at', '<=', $request->date_to);
        }

        $sessions = $query->paginate(20)->withQueryString();

        // Calculate session durations
        $sessions->getCollection()->transform(function ($session) {
            $duration = null;
            if ($session->logout_at) {
                $duration = $session->login_at->diffInMinutes($session->logout_at);
            }
            $session->duration_minutes = $duration;
            return $session;
        });

        return Inertia::render('SessionHistory/Index', [
            'sessions' => $sessions,
            'filters' => $request->only(['search', 'status', 'user_id', 'date_from', 'date_to']),
        ]);
    }
}
