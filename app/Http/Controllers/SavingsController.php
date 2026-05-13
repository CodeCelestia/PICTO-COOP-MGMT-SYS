<?php

namespace App\Http\Controllers;

use App\Models\Cooperative;
use App\Models\FinancialRecord;
use App\Models\Member;
use App\Models\MemberSavings;
use App\Models\SavingsTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsActivityWithChanges;
use Inertia\Inertia;
use Inertia\Response;

class SavingsController extends Controller
{
    use LogsActivityWithChanges;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = MemberSavings::query()->with('member:id,first_name,last_name');
        $cooperative = null;

        if ($this->isMemberOnly($user) && $user?->member_id) {
            $query->where('member_id', $user->member_id);
        } elseif ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('status')) {
            $query->where('account_status', $request->string('status'));
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', (int) $request->input('member_id'));
        }

        if ($request->filled('coop_id')) {
            $query->where('coop_id', (int) $request->input('coop_id'));
            $cooperative = \App\Models\Cooperative::select(['id', 'name'])->find($request->integer('coop_id'));
        }

        $savings = $query->latest()->paginate(15)->withQueryString();

        // Load cooperatives for global (non-coop) context drill-down
        $cooperatives = collect();
        if (!$request->filled('coop_id') && !$this->isMemberOnly($user)) {
            $cooperativesQuery = \App\Models\Cooperative::query()
                ->select([
                    'id',
                    'name',
                    'registration_number',
                    'status',
                    'classification',
                    'city_municipality',
                    'province',
                    'date_established',
                ])
                ->with([
                    'types' => function ($q) {
                        $q->select('cooperative_types.id', 'cooperative_types.name');
                    },
                    'accreditations' => function ($q) {
                        $q->orderByDesc('date_granted');
                    },
                ])
                ->withCount('members')
                ->orderBy('name');

            if ($request->filled('coop_search')) {
                $coopSearch = (string) $request->input('coop_search');
                $cooperativesQuery->where(function ($searchQuery) use ($coopSearch) {
                    $searchQuery->where('name', 'like', "%{$coopSearch}%")
                        ->orWhere('registration_number', 'like', "%{$coopSearch}%");
                });
            }

            if ($request->filled('coop_status')) {
                $cooperativesQuery->where('status', $request->string('coop_status'));
            }

            if ($request->filled('coop_type')) {
                $cooperativesQuery->whereHas('types', function ($builder) use ($request) {
                    $builder->where('name', $request->string('coop_type'));
                });
            }

            if ($request->filled('coop_classification')) {
                $cooperativesQuery->where('classification', $request->string('coop_classification'));
            }

            if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
                $cooperativesQuery->where('id', $user->coop_id);
            }

            $cooperatives = $cooperativesQuery->get()->transform(function ($cooperative) {
                $latestAccreditation = $cooperative->accreditations()
                    ->orderByDesc('date_granted')
                    ->first(['id', 'cooperative_id', 'level', 'date_granted']);

                $cooperative->setAttribute('latest_accreditation', $latestAccreditation ? [
                    'id' => $latestAccreditation->id,
                    'cooperative_id' => $latestAccreditation->cooperative_id,
                    'level' => $latestAccreditation->level,
                    'date_granted' => optional($latestAccreditation->date_granted)->toDateString(),
                ] : null);

                $cooperative->setAttribute('date_established', optional($cooperative->date_established)->toDateString());

                return $cooperative;
            });
        }

        return Inertia::render('Finance/Savings/Index', [
            'savings' => $savings,
            'cooperative' => $cooperative,
            'cooperatives' => $cooperatives,
            'accountStatuses' => ['Active', 'Dormant', 'Closed'],
            'filters' => $request->only(['status', 'coop_search', 'coop_status', 'coop_type', 'coop_classification']),
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
        if ($request->routeIs('cooperatives.finance.savings.*')) {
            $cooperative = $request->route('cooperative');
            if ($cooperative === 'my') {
                $cooperative = \App\Models\Cooperative::where('id', auth()->user()->cooperative_id)->firstOrFail();
                // Note: we cannot override the route parameter, so pass the resolved model to the view
            }
        }
        $user = $request->user();
        $isCoopContext = $request->routeIs('cooperatives.finance.savings.*');
        $coopContext = $isCoopContext ? $request->route('cooperative') : null;
        $coopId = null;
        if ($isCoopContext) {
            $coopId = is_string($coopContext) ? (int)$coopContext : $coopContext->id;
        } else {
            $coopId = $request->query('coop_id');
        }
        $cooperative = $coopId ? \App\Models\Cooperative::select(['id', 'name'])->find((int) $coopId) : null;

        if (!$user?->can('open finance-savings-accounts')) {
            abort(403, 'You do not have permission to open savings accounts.');
        }

        $membersQuery = Member::query()
            ->where('membership_status', 'Active')
            ->whereDoesntHave('savingsAccount')
            ->select(['id', 'first_name', 'last_name'])
            ->orderBy('last_name');

        if ($coopId) {
            $membersQuery->where('coop_id', (int) $coopId);
        }

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $membersQuery->where('coop_id', $user->coop_id);
        }

        return Inertia::render('Finance/Savings/Create', [
            'members' => $membersQuery->get(),
            'interestRate' => 3.0,
            'coop' => $cooperative ? ['id' => $cooperative->id, 'name' => $cooperative->name] : null,
            'isCoopContext' => $isCoopContext,
            'coopContext' => $coopContext,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user?->can('open finance-savings-accounts')) {
            abort(403, 'You do not have permission to open savings accounts.');
        }

        $validated = $request->validate([
            'member_id' => ['required', 'exists:members,id', 'unique:member_savings,member_id'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:10'],
        ]);

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

        $this->logDetailedActivity(
            'created',
            $savings,
            [],
            $savings->fresh()->getAttributes(),
            'Savings'
        );

        $safeReturnTo = $this->resolveInternalReturnTo($request);

        if ($safeReturnTo) {
            return redirect()->to($safeReturnTo)->with('success', 'Savings account created successfully.');
        }

        if ($request->routeIs('cooperatives.finance.savings.*')) {
            $cooperative = $request->route('cooperative');
            if ($cooperative === 'my') {
                $cooperative = \App\Models\Cooperative::where('id', auth()->user()->cooperative_id)->firstOrFail();
            }
            // Redirect back to Cooperatives/Show with tab=finance&subtab=savings to stay in component
            $coopId = is_object($cooperative) ? $cooperative->id : $cooperative;
            return redirect()->to("/cooperatives/{$coopId}?tab=finance&subtab=savings")
                ->with('success', 'Savings account created successfully.');
        }

        return redirect()->route('finance.savings.show', $savings)->with('success', 'Savings account created successfully.');
    }

    public function show($a = null, $b = null): Response
    {
        [$cooperative, $savings] = $this->resolveCoopAndSavings($a, $b);
        $request = request();

        $this->enforceSavingsAccess($savings, $request->user());

        if ($request->filled('coop_id') && (int) $request->input('coop_id') !== $savings->coop_id) {
            abort(403);
        }

        return Inertia::render('Finance/Savings/Show', [
            'savings' => $savings->load(['member:id,first_name,last_name', 'cooperative:id,name']),
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

    public function edit($a = null, $b = null): Response
    {
        [$cooperative, $savings] = $this->resolveCoopAndSavings($a, $b);
        $request = request();

        if ($request->routeIs('cooperatives.finance.savings.*')) {
            if ($cooperative === 'my') {
                $cooperative = \App\Models\Cooperative::where('id', auth()->user()->cooperative_id)->firstOrFail();
            }
        }

        $this->enforceSavingsAccess($savings, $request->user());

        if ($request->filled('coop_id') && (int) $request->input('coop_id') !== $savings->coop_id) {
            abort(403);
        }

        $isCoopContext = $request->routeIs('cooperatives.finance.savings.*');
        $coopContext = $isCoopContext ? $cooperative : null;

        return Inertia::render('Finance/Savings/Edit', [
            'savings' => $savings->load(['cooperative:id,name']),
            'isCoopContext' => $isCoopContext,
            'coopContext' => $coopContext,
        ]);
    }

    public function update($a = null, $b = null): RedirectResponse
    {
        [$cooperative, $savings] = $this->resolveCoopAndSavings($a, $b);
        $request = request();

        if (!$request->user()?->can('update finance-savings-accounts')) {
            abort(403, 'You do not have permission to update savings accounts.');
        }

        $this->enforceSavingsAccess($savings, $request->user());

        $validated = $request->validate([
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:10'],
            'account_status' => ['required', 'in:Active,Dormant,Closed'],
        ]);

        $oldValues = $savings->getAttributes();
        $savings->update($validated);

        $this->logDetailedActivity(
            'updated',
            $savings,
            $oldValues,
            $savings->fresh()->getAttributes(),
            'Savings'
        );

        $safeReturnTo = $this->resolveInternalReturnTo($request);

        if ($safeReturnTo) {
            return redirect()->to($safeReturnTo)->with('success', 'Savings account updated successfully.');
        }

        if ($request->routeIs('cooperatives.finance.savings.*')) {
            if ($cooperative === 'my') {
                $cooperative = \App\Models\Cooperative::where('id', auth()->user()->cooperative_id)->firstOrFail();
            }
            $coopId = is_object($cooperative) ? $cooperative->id : $cooperative;
            return redirect()->to("/cooperatives/{$coopId}?tab=finance&subtab=savings")
                ->with('success', 'Savings account updated successfully.');
        }

        return redirect()->route('finance.savings.show', $savings)->with('success', 'Savings account updated successfully.');
    }

    public function destroy($a = null, $b = null): RedirectResponse
    {
        [$cooperative, $savings] = $this->resolveCoopAndSavings($a, $b);
        $request = request();

        if (!$request->user()?->can('close finance-savings-accounts')) {
            abort(403, 'You do not have permission to close savings accounts.');
        }

        $this->enforceSavingsAccess($savings, $request->user());

        $oldValues = $savings->getAttributes();
        $savings->update([
            'account_status' => 'Closed',
            'closed_at' => now()->toDateString(),
        ]);

        $this->logDetailedActivity(
            'deleted',
            $savings,
            $oldValues,
            $savings->fresh()->getAttributes(),
            'Savings'
        );

        if ($request->routeIs('cooperatives.finance.savings.*')) {
            if ($cooperative === 'my') {
                $cooperative = \App\Models\Cooperative::where('id', auth()->user()->cooperative_id)->firstOrFail();
            }
            $coopId = is_object($cooperative) ? $cooperative->id : $cooperative;
            return redirect()->to("/cooperatives/{$coopId}?tab=finance&subtab=savings")
                ->with('success', 'Savings account closed successfully.');
        }

        return redirect()->route('finance.savings.index')->with('success', 'Savings account closed successfully.');
    }

    public function calculateInterest($a = null, $b = null): RedirectResponse
    {
        [$cooperative, $savings] = $this->resolveCoopAndSavings($a, $b);
        $request = request();

        if (!$request->user()?->can('calculate-interest finance-savings-accounts') && !$request->user()?->can('override finance-auto-jobs')) {
            abort(403, 'You do not have permission to calculate savings interest.');
        }

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

    public function recordTransaction($a = null, $b = null): RedirectResponse
    {
        [$cooperative, $savings] = $this->resolveCoopAndSavings($a, $b);
        $request = request();

        if (!$request->user()?->can('record-deposit finance-savings-accounts') && !$request->user()?->can('record-withdrawal finance-savings-accounts')) {
            abort(403, 'You do not have permission to record savings transactions.');
        }

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

    /**
     * Normalize incoming parameters for routes that may be either:
     * - /finance/savings/{savings}
     * - /cooperatives/{cooperative}/finance/savings/{savings}
     * Accepts either (MemberSavings) or (cooperative, MemberSavings) or numeric ids.
     * Returns [$cooperative, MemberSavings $savings]
     */
    private function resolveCoopAndSavings($a = null, $b = null): array
    {
        // If first arg is a MemberSavings instance and second is null => global route
        if ($a instanceof MemberSavings && $b === null) {
            return [null, $a];
        }

        // If second arg is a MemberSavings instance => per-coop route
        if ($b instanceof MemberSavings) {
            // If $a is numeric string, convert to Cooperative instance
            if (is_numeric($a)) {
                $cooperative = Cooperative::findOrFail((int) $a);
                return [$cooperative, $b];
            }
            return [$a, $b];
        }

        // If first is numeric (id) and second is null => global route by id
        if (is_numeric($a) && $b === null) {
            $savings = MemberSavings::findOrFail((int) $a);
            return [null, $savings];
        }

        // If second is numeric id => per-coop route by id
        if ($b !== null && is_numeric($b)) {
            $savings = MemberSavings::findOrFail((int) $b);
            // If $a is numeric string, convert to Cooperative instance
            if (is_numeric($a)) {
                $cooperative = Cooperative::findOrFail((int) $a);
                return [$cooperative, $savings];
            }
            return [$a, $savings];
        }

        // Fallback: attempt to resolve from route
        $routeSavings = request()->route('savings');
        if ($routeSavings instanceof MemberSavings) {
            return [request()->route('cooperative') ?? null, $routeSavings];
        }

        abort(400, 'Unable to resolve savings account.');
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
