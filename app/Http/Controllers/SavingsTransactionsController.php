<?php

namespace App\Http\Controllers;

use App\Models\FinancialRecord;
use App\Models\MemberSavings;
use App\Models\SavingsTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingsTransactionsController extends Controller
{
    public function store(Request $request, MemberSavings $savings): RedirectResponse
    {
        $user = $request->user();

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id && $savings->coop_id !== $user->coop_id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:Deposit,Withdrawal'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validated['type'] === 'Withdrawal' && (float) $validated['amount'] > (float) $savings->current_balance) {
            return back()->withErrors(['amount' => 'Insufficient balance for withdrawal.']);
        }

        DB::transaction(function () use ($validated, $savings, $user) {
            $newBalance = $validated['type'] === 'Deposit'
                ? (float) $savings->current_balance + (float) $validated['amount']
                : (float) $savings->current_balance - (float) $validated['amount'];

            SavingsTransaction::create([
                'member_savings_id' => $savings->id,
                'coop_id' => $savings->coop_id,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'balance_after' => $newBalance,
                'remarks' => $validated['remarks'] ?? null,
                'recorded_by' => $user?->id,
                'recorded_at' => now(),
            ]);

            $savings->update(['current_balance' => $newBalance]);

            FinancialRecord::create([
                'coop_id' => $savings->coop_id,
                'period' => now()->format('Y-m'),
                'type' => $validated['type'] === 'Deposit' ? 'Income' : 'Expense',
                'amount' => $validated['amount'],
                'source' => 'Savings ' . $validated['type'],
                'purpose' => 'Savings account transaction',
                'date_recorded' => now()->toDateString(),
                'recorded_by' => $user?->name,
            ]);
        });

        return back()->with('success', 'Savings transaction recorded successfully.');
    }
}
