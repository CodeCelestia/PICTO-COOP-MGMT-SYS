<?php

namespace App\Http\Controllers;

use App\Models\FinancialRecord;
use App\Models\LoanPayment;
use App\Models\MemberLoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanPaymentsController extends Controller
{
    public function store(Request $request, MemberLoan $loan): RedirectResponse
    {
        $user = $request->user();

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id && $loan->coop_id !== $user->coop_id) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($loan, $validated, $user) {
            $schedule = $loan->payments()
                ->whereNotNull('payment_number')
                ->where('status', 'Pending')
                ->orderBy('payment_number')
                ->first();

            $remainingBefore = $loan->getRemainingBalance();
            $newBalance = max(0, $remainingBefore - (float) $validated['amount']);

            if ($schedule) {
                $isPaid = (float) $validated['amount'] >= (float) ($schedule->total_due ?? 0);

                $schedule->update([
                    'amount_paid' => $validated['amount'],
                    'paid_at' => $validated['paid_at'] ?? now(),
                    'balance_after' => $newBalance,
                    'status' => $isPaid ? 'Paid' : 'Partial',
                    'remarks' => $validated['remarks'] ?? null,
                    'recorded_by' => $user?->id,
                ]);
            } else {
                LoanPayment::create([
                    'loan_id' => $loan->id,
                    'coop_id' => $loan->coop_id,
                    'amount_paid' => $validated['amount'],
                    'paid_at' => $validated['paid_at'] ?? now(),
                    'balance_after' => $newBalance,
                    'status' => 'Paid',
                    'remarks' => $validated['remarks'] ?? null,
                    'recorded_by' => $user?->id,
                ]);
            }

            FinancialRecord::create([
                'coop_id' => $loan->coop_id,
                'period' => now()->format('Y-m'),
                'type' => 'Income',
                'amount' => $validated['amount'],
                'source' => 'Member Loan Payment',
                'purpose' => 'Loan payment for member #' . $loan->member_id,
                'date_recorded' => now()->toDateString(),
                'recorded_by' => $user?->name,
            ]);

            if ($newBalance <= 0) {
                $loan->update(['status' => 'Completed']);
            }
        });

        return back()->with('success', 'Loan payment recorded successfully.');
    }
}
