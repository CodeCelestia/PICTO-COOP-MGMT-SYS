<?php

namespace App\Http\Controllers;

use App\Models\FinancialRecord;
use App\Models\Member;
use App\Models\MemberSavings;
use App\Models\SavingsTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SavingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = MemberSavings::query()->with('member:id,first_name,last_name');

        if ($this->isMemberOnly($user) && $user?->member_id) {
            $query->where('member_id', $user->member_id);
        } elseif ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('status')) {
            $query->where('account_status', $request->string('status'));
        }

        $savings = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Finance/Savings/Index', [
            'savings' => $savings,
            'accountStatuses' => ['Active', 'Dormant', 'Closed'],
            'filters' => $request->only(['status']),
            'permissions' => [
                'can_create' => $user?->can('open finance-savings-accounts') ?? false,
                'can_edit' => $user?->can('update finance-savings-accounts') ?? false,
                'can_close' => $user?->can('close finance-savings-accounts') ?? false,
                'can_record_transaction' => ($user?->can('record-deposit finance-savings-accounts') ?? false) || ($user?->can('record-withdrawal finance-savings-accounts') ?? false),
                'can_calculate_interest' => ($user?->can('calculate-interest finance-savings-accounts') ?? false) || ($user?->can('override finance-auto-jobs') ?? false),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        $membersQuery = Member::query()
            ->where('membership_status', 'Active')
            ->whereDoesntHave('savingsAccount')
            ->select(['id', 'first_name', 'last_name'])
            ->orderBy('last_name');

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $membersQuery->where('coop_id', $user->coop_id);
        }

        return Inertia::render('Finance/Savings/Create', [
            'members' => $membersQuery->get(),
            'interestRate' => 3.0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,id', 'unique:member_savings,member_id'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:10'],
        ]);

        $user = $request->user();
        $memberQuery = Member::query()->where('id', $validated['member_id']);
        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $memberQuery->where('coop_id', $user->coop_id);
        }
        $member = $memberQuery->firstOrFail();

        $savings = DB::transaction(function () use ($validated, $member, $user) {
            $savings = MemberSavings::create([
                'coop_id' => $member->coop_id,
                'member_id' => $member->id,
                'account_number' => $this->generateAccountNumber($member->coop_id),
                'account_status' => 'Active',
                'current_balance' => $validated['opening_balance'],
                'interest_rate' => $validated['interest_rate'],
                'opened_at' => now()->toDateString(),
                'created_by' => $user?->id,
            ]);

            if ((float) $validated['opening_balance'] > 0) {
                SavingsTransaction::create([
                    'member_savings_id' => $savings->id,
                    'coop_id' => $savings->coop_id,
                    'type' => 'Deposit',
                    'amount' => $validated['opening_balance'],
                    'balance_after' => $validated['opening_balance'],
                    'recorded_by' => $user?->id,
                    'recorded_at' => now(),
                ]);
            }

            return $savings;
        });

        return redirect()->route('finance.savings.show', $savings)
            ->with('success', 'Savings account created successfully.');
    }

    public function show(MemberSavings $savings, Request $request): Response
    {
        $this->enforceSavingsAccess($savings, $request->user());

        return Inertia::render('Finance/Savings/Show', [
            'savings' => $savings->load(['member:id,first_name,last_name']),
            'transactions' => $savings->transactions()->latest()->paginate(10)->withQueryString(),
            'totalInterestEarned' => $savings->transactions()->where('type', 'Interest')->sum('amount'),
            'permissions' => [
                'can_edit' => $request->user()?->can('update finance-savings-accounts') ?? false,
                'can_close' => $request->user()?->can('close finance-savings-accounts') ?? false,
                'can_record_transaction' => ($request->user()?->can('record-deposit finance-savings-accounts') ?? false) || ($request->user()?->can('record-withdrawal finance-savings-accounts') ?? false),
                'can_calculate_interest' => ($request->user()?->can('calculate-interest finance-savings-accounts') ?? false) || ($request->user()?->can('override finance-auto-jobs') ?? false),
            ],
        ]);
    }

    public function edit(MemberSavings $savings, Request $request): Response
    {
        $this->enforceSavingsAccess($savings, $request->user());

        return Inertia::render('Finance/Savings/Edit', [
            'savings' => $savings,
        ]);
    }

    public function update(Request $request, MemberSavings $savings): RedirectResponse
    {
        $this->enforceSavingsAccess($savings, $request->user());

        $validated = $request->validate([
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:10'],
            'account_status' => ['required', 'in:Active,Dormant,Closed'],
        ]);

        $savings->update($validated);

        return redirect()->route('finance.savings.show', $savings)
            ->with('success', 'Savings account updated successfully.');
    }

    public function destroy(MemberSavings $savings, Request $request): RedirectResponse
    {
        $this->enforceSavingsAccess($savings, $request->user());

        $savings->update([
            'account_status' => 'Closed',
            'closed_at' => now()->toDateString(),
        ]);

        return redirect()->route('finance.savings.index')
            ->with('success', 'Savings account closed successfully.');
    }

    public function calculateInterest(Request $request, MemberSavings $savings): RedirectResponse
    {
        $this->enforceSavingsAccess($savings, $request->user());

        $interestAmount = ((float) $savings->current_balance * (float) $savings->interest_rate / 100) / 12;

        DB::transaction(function () use ($savings, $interestAmount, $request) {
            $newBalance = (float) $savings->current_balance + $interestAmount;

            SavingsTransaction::create([
                'member_savings_id' => $savings->id,
                'coop_id' => $savings->coop_id,
                'type' => 'Interest',
                'amount' => $interestAmount,
                'balance_after' => $newBalance,
                'recorded_by' => $request->user()?->id,
                'recorded_at' => now(),
            ]);

            $savings->update([
                'current_balance' => $newBalance,
                'last_interest_calculated' => now(),
            ]);

            FinancialRecord::create([
                'coop_id' => $savings->coop_id,
                'period' => now()->format('Y-m'),
                'type' => 'Expense',
                'amount' => $interestAmount,
                'source' => 'Savings Interest Credit',
                'purpose' => 'Monthly interest credit',
                'date_recorded' => now()->toDateString(),
                'recorded_by' => $request->user()?->name,
            ]);
        });

        return back()->with('success', 'Interest calculated and applied.');
    }

    public function recordTransaction(Request $request, MemberSavings $savings): RedirectResponse
    {
        $this->enforceSavingsAccess($savings, $request->user());

        $validated = $request->validate([
            'type' => ['required', 'in:Deposit,Withdrawal'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validated['type'] === 'Withdrawal' && (float) $validated['amount'] > (float) $savings->current_balance) {
            return back()->withErrors(['amount' => 'Insufficient balance for withdrawal.']);
        }

        DB::transaction(function () use ($validated, $savings, $request) {
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
                'recorded_by' => $request->user()?->id,
                'recorded_at' => now(),
            ]);

            $savings->update(['current_balance' => $newBalance]);
        });

        return back()->with('success', $validated['type'] . ' recorded successfully.');
    }

    private function enforceSavingsAccess(MemberSavings $savings, $user): void
    {
        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id && $savings->coop_id !== $user->coop_id) {
            abort(403);
        }

        if ($this->isMemberOnly($user) && $user?->member_id && $savings->member_id !== $user->member_id) {
            abort(403);
        }
    }

    private function isMemberOnly($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can('record-deposit finance-savings-accounts')
            && ! $user->can('open finance-savings-accounts');
    }

    private function generateAccountNumber(int $coopId): string
    {
        return sprintf('SV-%d-%s-%04d', $coopId, now()->format('Ymd'), random_int(1, 9999));
    }
}
