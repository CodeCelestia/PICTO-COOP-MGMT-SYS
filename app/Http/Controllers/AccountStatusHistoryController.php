<?php

namespace App\Http\Controllers;

use App\Models\AccountStatusHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountStatusHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountStatusHistory::with('user:id,name,email,account_status')
            ->orderBy('changed_at', 'desc');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('changed_by', 'like', "%{$search}%");
        }

        // Status filter
        if ($request->filled('new_status')) {
            $query->where('new_status', $request->new_status);
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('changed_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('changed_at', '<=', $request->date_to);
        }

        $histories = $query->paginate(20)->withQueryString();

        return Inertia::render('AccountStatusHistory/Index', [
            'histories' => $histories,
            'filters' => $request->only(['search', 'new_status', 'user_id', 'date_from', 'date_to']),
        ]);
    }
}
