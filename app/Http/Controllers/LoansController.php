<?php

namespace App\Http\Controllers;

use App\Models\FinancialRecord;
use App\Models\LoanType;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\MemberLoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Traits\LogsActivityWithChanges;
use Inertia\Inertia;
use Inertia\Response;

class LoansController extends Controller
{
    use LogsActivityWithChanges;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = MemberLoan::query()->with(['member:id,first_name,last_name', 'cooperative:id,name', 'loanType:id,name']);

        if ($this->isMemberOnly($user) && $user?->member_id) {
            $query->where('member_id', $user->member_id);
        } elseif ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('member_id') && ! $this->isMemberOnly($user)) {
            $query->where('member_id', (int) $request->input('member_id'));
        }

        $loans = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Finance/Loans/Index', [
            'loans' => $loans,
            'statuses' => ['Pending', 'Approved', 'Active', 'Completed', 'Defaulted', 'Rejected'],
            'filters' => $request->only(['status', 'member_id']),
            'permissions' => [
                'can_create' => ($user?->can('create finance-member-loans') ?? false) || ($user?->can('apply-own finance-member-loans') ?? false),
                'can_approve' => ($user?->can('approve finance-member-loans') ?? false) || ($user?->can('approve-major finance-member-loans') ?? false),
                'can_disburse' => $user?->can('disburse finance-member-loans') ?? false,
                'can_edit' => $user?->can('update finance-member-loans') ?? false,
                'can_delete' => $user?->can('delete finance-member-loans') ?? false,
                'can_record_payment' => $user?->can('record-payment finance-member-loans') ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        if (!$user?->can('create finance-member-loans') && !$user?->can('apply-own finance-member-loans')) {
            abort(403, 'You do not have permission to create loan applications.');
        }

        if ($this->isMemberOnly($user) && ! $user?->member_id) {
            abort(403);
        }

        $isSuperOrProvincialAdmin = (bool) ($user?->hasRole(['Super Admin', 'Provincial Admin']));
        $preselectedCooperativeId = null;

        if ($isSuperOrProvincialAdmin) {
            $requestedCooperativeId = (int) $request->input('cooperative_id');
            if ($requestedCooperativeId > 0) {
                $preselectedCooperativeId = $requestedCooperativeId;
            }
        }

        $membersQuery = Member::query()
            ->select(['id', 'first_name', 'last_name', 'coop_id'])
            ->with('cooperative:id,classification')
            ->where('membership_status', 'Active')
            ->orderBy('last_name');

        if ($this->isMemberOnly($user) && $user?->member_id) {
            $membersQuery->where('id', $user->member_id);
        } elseif ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $membersQuery->where('coop_id', $user->coop_id);
        }

        $loanTypesQuery = LoanType::query()
            ->select(['id', 'name', 'cooperative_id', 'classification'])
            ->where('is_active', true)
            ->orderBy('name');

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $loanTypesQuery->where('cooperative_id', $user->coop_id);
        }

        $cooperatives = collect();

        if ($isSuperOrProvincialAdmin) {
            $cooperatives = \App\Models\Cooperative::query()
                ->select(['id', 'name', 'classification'])
                ->orderBy('name')
                ->get()
                ->map(function ($cooperative) {
                    $members = Member::query()
                        ->select(['id', 'first_name', 'last_name', 'coop_id'])
                        ->where('membership_status', 'Active')
                        ->where('coop_id', $cooperative->id)
                        ->orderBy('last_name')
                        ->get();

                    $loanTypes = LoanType::query()
                        ->select(['id', 'name', 'cooperative_id', 'classification'])
                        ->where('is_active', true)
                        ->where('cooperative_id', $cooperative->id)
                        ->orderBy('name')
                        ->get();

                    return [
                        'id' => $cooperative->id,
                        'name' => $cooperative->name,
                        'classification' => $cooperative->classification,
                        'members' => $members,
                        'loan_types' => $loanTypes,
                    ];
                })
                ->values();
        }

        $members = $membersQuery->get();
        $loanTypes = $loanTypesQuery->get();

        if ($isSuperOrProvincialAdmin) {
            if ($preselectedCooperativeId) {
                $selectedCooperative = $cooperatives->firstWhere('id', $preselectedCooperativeId);
                $members = collect($selectedCooperative['members'] ?? []);
                $loanTypes = collect($selectedCooperative['loan_types'] ?? []);
            } else {
                $members = collect();
                $loanTypes = collect();
            }
        }

        return Inertia::render('Finance/Loans/Create', [
            'members' => $members,
            'loanTypes' => $loanTypes,
            'cooperatives' => $cooperatives,
            'showCooperativePicker' => $isSuperOrProvincialAdmin,
            'preselectedCooperativeId' => $preselectedCooperativeId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user?->can('create finance-member-loans') && !$user?->can('apply-own finance-member-loans')) {
            abort(403, 'You do not have permission to create loan applications.');
        }

        $validated = $request->validate([
            'member_id' => ['nullable', 'exists:members,id'],
            'loan_type_id' => ['required', 'exists:loan_types,id'],
            'principal' => ['required', 'numeric', 'min:0'],
            'purpose' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        if ($this->isMemberOnly($user)) {
            $validated['member_id'] = $user?->member_id;
        }

        if (! $validated['member_id']) {
            return back()->withErrors(['member_id' => 'Member is required.']);
        }

        $memberQuery = Member::query()->with('cooperative:id,classification')->where('id', $validated['member_id']);
        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $memberQuery->where('coop_id', $user->coop_id);
        }
        $member = $memberQuery->firstOrFail();

        $loanTypeQuery = LoanType::query()->where('id', $validated['loan_type_id']);
        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $loanTypeQuery->where('cooperative_id', $user->coop_id);
        }
        $loanType = $loanTypeQuery->firstOrFail();

        if (! $loanType->is_active) {
            return back()->withErrors(['loan_type_id' => 'Selected loan type is not active.']);
        }

        if ((int) $loanType->cooperative_id !== (int) $member->coop_id) {
            return back()->withErrors(['loan_type_id' => 'Selected loan type does not belong to the member cooperative.']);
        }

        $cooperativeClassification = $member->cooperative?->classification;
        if ($cooperativeClassification && $loanType->classification && $loanType->classification !== $cooperativeClassification) {
            return back()->withErrors(['loan_type_id' => 'Selected loan type classification does not match the cooperative classification.']);
        }

        $loan = DB::transaction(function () use ($validated, $member, $loanType, $request, $user) {
            $loan = MemberLoan::create([
                'coop_id' => $member->coop_id,
                'member_id' => $member->id,
                'loan_type_id' => $loanType->id,
                'principal' => $validated['principal'],
                'interest_rate' => 0,
                'term_months' => 1,
                'status' => 'Pending',
                'purpose' => $validated['purpose'] ?? null,
                'created_by' => $user?->id,
            ]);

            if ((float) $loan->principal > 0 && (int) $loan->term_months > 0) {
                $this->generateRepaymentSchedule($loan);
            }

            $this->attachUploadedFiles($loan, $request->file('attachments', []));

            return $loan;
        });

        $this->logDetailedActivity(
            'created',
            $loan,
            [],
            $loan->fresh()->getAttributes(),
            'Loans'
        );

        $safeReturnTo = $this->resolveInternalReturnTo($request);

        return $safeReturnTo
            ? redirect()->to($safeReturnTo)->with('success', 'Loan application created successfully.')
            : redirect()->route('finance.loans.show', $loan)->with('success', 'Loan application created successfully.');
    }

    public function show(MemberLoan $loan, Request $request): Response
    {
        $this->enforceLoanAccess($loan, $request->user());

        $loan->load(['member:id,first_name,last_name', 'cooperative:id,name', 'loanType:id,name', 'payments']);
        $memberLoanCount = MemberLoan::withTrashed()
            ->where('member_id', $loan->member_id)
            ->count();

        return Inertia::render('Finance/Loans/Show', [
            'loan' => $loan,
            'memberLoanCount' => $memberLoanCount,
            'repaymentSchedule' => $loan->getRepaymentSchedule(),
            'remainingBalance' => $loan->getRemainingBalance(),
            'nextPaymentDue' => $loan->getNextPaymentDue(),
            'permissions' => [
                'can_approve' => ($request->user()?->can('approve finance-member-loans') ?? false) || ($request->user()?->can('approve-major finance-member-loans') ?? false),
                'can_disburse' => $request->user()?->can('disburse finance-member-loans') ?? false,
                'can_edit' => $request->user()?->can('update finance-member-loans') ?? false,
                'can_delete' => $request->user()?->can('delete finance-member-loans') ?? false,
                'can_record_payment' => $request->user()?->can('record-payment finance-member-loans') ?? false,
            ],
        ]);
    }

    public function edit(MemberLoan $loan, Request $request): Response
    {
        $this->enforceLoanAccess($loan, $request->user());

        $attachmentContext = $this->extractLoanAttachmentContext($loan->remarks);
        $loan->setAttribute('attachments', $this->formatLoanAttachments($attachmentContext['paths']));
        $from = $request->query('from') === 'coop' ? 'coop' : null;

        return Inertia::render('Finance/Loans/Edit', [
            'loan' => $loan->load(['member:id,first_name,last_name']),
            'from' => $from,
            'cooperative_id' => $from === 'coop' ? $loan->coop_id : null,
        ]);
    }

    public function update(Request $request, MemberLoan $loan): RedirectResponse
    {
        if (!$request->user()?->can('update finance-member-loans')) {
            abort(403, 'You do not have permission to update loans.');
        }

        $this->enforceLoanAccess($loan, $request->user());

        $validated = $request->validate([
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'term_months' => ['required', 'integer', 'min:1', 'max:60'],
            'purpose' => ['nullable', 'string'],
            'status' => ['required', 'in:Pending,Approved,Active,Completed,Defaulted,Rejected'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:5120'],
            'attachments_removed' => ['nullable', 'array'],
            'attachments_removed.*' => ['string'],
        ]);

        $attachmentContext = $this->extractLoanAttachmentContext($loan->remarks);
        $remainingAttachmentPaths = array_values(array_filter(
            $attachmentContext['paths'],
            fn (string $path) => ! in_array($path, $validated['attachments_removed'] ?? [], true)
        ));

        $newAttachmentPaths = [];
        foreach ($request->file('attachments', []) as $attachment) {
            if (! $attachment instanceof UploadedFile) {
                continue;
            }

            $newAttachmentPaths[] = $attachment->store("loan-attachments/{$loan->id}", 'public');
        }

        $validated['remarks'] = $this->buildLoanRemarks(
            $attachmentContext['remarks'],
            array_values(array_unique(array_merge($remainingAttachmentPaths, $newAttachmentPaths)))
        );

        $oldValues = $loan->getAttributes();
        $loan->update($validated);

        $this->logDetailedActivity(
            'updated',
            $loan,
            $oldValues,
            $loan->fresh()->getAttributes(),
            'Loans'
        );

        $safeReturnTo = $this->resolveInternalReturnTo($request);

        return $safeReturnTo
            ? redirect()->to($safeReturnTo)->with('success', 'Loan updated successfully.')
            : redirect()->route('finance.loans.show', $loan)->with('success', 'Loan updated successfully.');
    }

    public function destroy(MemberLoan $loan, Request $request): RedirectResponse
    {
        if (!$request->user()?->can('delete finance-member-loans')) {
            abort(403, 'You do not have permission to delete loans.');
        }

        $this->enforceLoanAccess($loan, $request->user());

        $oldValues = $loan->getAttributes();
        $loan->delete();

        $this->logDetailedActivity(
            'deleted',
            $loan,
            $oldValues,
            [],
            'Loans'
        );

        return redirect()->route('finance.loans.index')
            ->with('success', 'Loan deleted successfully.');
    }

    public function approve(Request $request, MemberLoan $loan): RedirectResponse
    {
        if (!$request->user()?->can('approve finance-member-loans') && !$request->user()?->can('approve-major finance-member-loans')) {
            abort(403, 'You do not have permission to approve loans.');
        }

        $this->enforceLoanAccess($loan, $request->user());

        if (! in_array($loan->status, ['Pending', 'Rejected'], true)) {
            return back()->withErrors(['error' => 'Only pending or rejected loans can be approved.']);
        }

        $oldValues = $loan->getAttributes();

        $loan->update([
            'status' => 'Approved',
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        $this->logDetailedActivity(
            'approved',
            $loan,
            $oldValues,
            $loan->fresh()->getAttributes(),
            'Loans'
        );

        return back()->with('success', 'Loan approved successfully.');
    }

    public function disburse(Request $request, MemberLoan $loan): RedirectResponse
    {
        if (!$request->user()?->can('disburse finance-member-loans')) {
            abort(403, 'You do not have permission to disburse loans.');
        }

        $this->enforceLoanAccess($loan, $request->user());

        if (! in_array($loan->status, ['Approved', 'Active'], true)) {
            return back()->withErrors(['error' => 'Only approved loans can be disbursed.']);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'disbursement_method' => ['required', 'in:check,cash,bank_transfer'],
            'remarks' => ['nullable', 'string'],
        ]);

        $oldValues = $loan->getAttributes();

        DB::transaction(function () use ($loan, $validated, $request) {
            $loan->loadMissing(['member:id,first_name,last_name', 'loanType:id,name']);

            $loan->update([
                'status' => 'Active',
                'disbursement_date' => now(),
                'amount_disbursed' => $validated['amount'],
                'disbursement_method' => $validated['disbursement_method'],
                'disburse_remarks' => $validated['remarks'] ?? null,
            ]);

            FinancialRecord::create([
                'coop_id' => $loan->coop_id,
                'period' => now()->format('Y-m'),
                'type' => 'Loan',
                'amount' => $loan->principal,
                'source' => 'loan_release',
                'purpose' => 'Loan released to ' . ($loan->member?->full_name ?? 'Unknown Member') . ' - ' . ($loan->loanType?->name ?? 'Unspecified Loan Type'),
                'date_recorded' => now()->toDateString(),
                'reference_doc' => (string) $loan->id,
                'recorded_by' => $request->user()?->name,
            ]);
        });

        $this->logDetailedActivity(
            'disbursed',
            $loan,
            $oldValues,
            $loan->fresh()->getAttributes(),
            'Loans'
        );

        return back()->with('success', 'Loan disbursed successfully.');
    }

    private function generateRepaymentSchedule(MemberLoan $loan): void
    {
        $monthlyPayment = $this->calculateMonthlyPayment(
            (float) $loan->principal,
            (float) $loan->interest_rate,
            (int) $loan->term_months
        );

        $remainingBalance = (float) $loan->principal;
        $dueDate = now()->addMonth();

        for ($i = 1; $i <= (int) $loan->term_months; $i++) {
            $interest = ($remainingBalance * (float) $loan->interest_rate / 100) / 12;
            $principalDue = max(0, $monthlyPayment - $interest);
            $remainingBalance -= $principalDue;

            LoanPayment::create([
                'loan_id' => $loan->id,
                'coop_id' => $loan->coop_id,
                'payment_number' => $i,
                'principal_due' => $principalDue,
                'interest_due' => $interest,
                'total_due' => $monthlyPayment,
                'due_date' => $dueDate->toDateString(),
                'balance_after' => max(0, $remainingBalance),
                'status' => 'Pending',
            ]);

            $dueDate = $dueDate->copy()->addMonth();
        }
    }

    private function calculateMonthlyPayment(float $principal, float $annualRate, int $months): float
    {
        if ($months <= 0) {
            return 0;
        }

        $monthlyRate = $annualRate / 100 / 12;

        if ($monthlyRate == 0.0) {
            return $principal / $months;
        }

        return $principal * ($monthlyRate * (1 + $monthlyRate) ** $months)
            / ((1 + $monthlyRate) ** $months - 1);
    }

    private function enforceLoanAccess(MemberLoan $loan, $user): void
    {
        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id && $loan->coop_id !== $user->coop_id) {
            abort(403);
        }

        if ($this->isMemberOnly($user) && $user?->member_id && $loan->member_id !== $user->member_id) {
            abort(403);
        }
    }

    private function isMemberOnly($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can('apply-own finance-member-loans')
            && ! $user->can('create finance-member-loans');
    }

    /**
     * @param  UploadedFile[]  $attachments
     */
    private function attachUploadedFiles(MemberLoan $loan, array $attachments): void
    {
        if (empty($attachments)) {
            return;
        }

        $storedPaths = [];
        foreach ($attachments as $attachment) {
            if (! $attachment instanceof UploadedFile) {
                continue;
            }

            $storedPaths[] = $attachment->store("loan-attachments/{$loan->id}", 'public');
        }

        if (empty($storedPaths)) {
            return;
        }

        $existingRemarks = trim((string) ($loan->remarks ?? ''));
        $attachmentLines = implode("\n", array_map(static fn (string $path) => "- {$path}", $storedPaths));
        $attachmentBlock = "Attachments:\n{$attachmentLines}";

        $loan->update([
            'remarks' => trim($existingRemarks === '' ? $attachmentBlock : "{$existingRemarks}\n\n{$attachmentBlock}"),
        ]);
    }

    /**
     * @return array{remarks: string, paths: array<int, string>}
     */
    private function extractLoanAttachmentContext(?string $remarks): array
    {
        $normalizedRemarks = trim((string) $remarks);

        if ($normalizedRemarks === '') {
            return ['remarks' => '', 'paths' => []];
        }

        $marker = "\n\nAttachments:\n";
        $markerPosition = strpos($normalizedRemarks, $marker);

        if ($markerPosition === false) {
            return ['remarks' => $normalizedRemarks, 'paths' => []];
        }

        $baseRemarks = trim(substr($normalizedRemarks, 0, $markerPosition));
        $attachmentBlock = trim(substr($normalizedRemarks, $markerPosition + strlen($marker)));
        $paths = [];

        foreach (preg_split('/\r?\n/', $attachmentBlock) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || ! str_starts_with($line, '- ')) {
                continue;
            }

            $path = trim(substr($line, 2));

            if ($path !== '') {
                $paths[] = $path;
            }
        }

        return [
            'remarks' => $baseRemarks,
            'paths' => $paths,
        ];
    }

    /**
     * @param array<int, string> $attachmentPaths
     * @return array<int, array{path: string, name: string, url: string, extension: string}>
     */
    private function formatLoanAttachments(array $attachmentPaths): array
    {
        return array_values(array_map(function (string $path) {
            $fileName = basename($path);

            return [
                'path' => $path,
                'name' => $fileName,
                'url' => Storage::disk('public')->url($path),
                'extension' => strtolower(pathinfo($fileName, PATHINFO_EXTENSION)),
            ];
        }, array_values(array_filter($attachmentPaths, static fn ($path) => is_string($path) && trim($path) !== ''))));
    }

    /**
     * @param array<int, string> $attachmentPaths
     */
    private function buildLoanRemarks(string $baseRemarks, array $attachmentPaths): ?string
    {
        $cleanBaseRemarks = trim($baseRemarks);
        $cleanAttachmentPaths = array_values(array_filter(array_map('trim', $attachmentPaths), static fn (string $path) => $path !== ''));

        if (empty($cleanAttachmentPaths)) {
            return $cleanBaseRemarks !== '' ? $cleanBaseRemarks : null;
        }

        $attachmentLines = implode("\n", array_map(static fn (string $path) => "- {$path}", $cleanAttachmentPaths));
        $attachmentBlock = "Attachments:\n{$attachmentLines}";

        return $cleanBaseRemarks === '' ? $attachmentBlock : "{$cleanBaseRemarks}\n\n{$attachmentBlock}";
    }
}
