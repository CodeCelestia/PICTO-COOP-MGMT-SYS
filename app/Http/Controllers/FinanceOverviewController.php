<?php

namespace App\Http\Controllers;

use App\Models\ActivityFundingSource;
use App\Models\Cooperative;
use App\Models\ExternalSupport;
use App\Models\FinancialRecord;
use App\Models\MemberLoan;
use App\Models\MemberSavings;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceOverviewController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        $canViewAllCooperatives = (bool) ($user?->can('view-all-cooperatives') ?? false);

        $scopeLabel = 'all cooperatives';
        if (! $canViewAllCooperatives && $user?->coop_id) {
            $scopeLabel = Cooperative::query()
                ->whereKey($user->coop_id)
                ->value('name') ?? 'your cooperative';
        }

        $loanQuery = MemberLoan::query();
        $savingsQuery = MemberSavings::query();
        $recordQuery = FinancialRecord::query();
        $fundingSourceQuery = ActivityFundingSource::query();
        $externalSupportQuery = ExternalSupport::query();

        $this->applyScope($loanQuery, $user);
        $this->applyScope($savingsQuery, $user);
        $this->applyScope($recordQuery, $user);
        $this->applyScope($fundingSourceQuery, $user);
        $this->applyScope($externalSupportQuery, $user);

        return Inertia::render('Finance/Index', [
            'scopeLabel' => $scopeLabel,
            'summary' => [
                'loans' => [
                    'total' => $loanQuery->count(),
                    'pending' => (clone $loanQuery)->where('status', 'Pending')->count(),
                    'active' => (clone $loanQuery)->where('status', 'Active')->count(),
                    'completed' => (clone $loanQuery)->where('status', 'Completed')->count(),
                    'principal_total' => (float) (clone $loanQuery)->sum('principal'),
                ],
                'savings' => [
                    'total_accounts' => $savingsQuery->count(),
                    'active_accounts' => (clone $savingsQuery)->where('account_status', 'Active')->count(),
                    'total_balance' => (float) (clone $savingsQuery)->sum('current_balance'),
                ],
                'financial_records' => [
                    'total' => $recordQuery->count(),
                    'income' => (float) (clone $recordQuery)->where('type', 'Income')->sum('amount'),
                    'expense' => (float) (clone $recordQuery)->where('type', 'Expense')->sum('amount'),
                    'net_surplus' => (float) (clone $recordQuery)->sum('net_surplus'),
                ],
                'funding_sources' => [
                    'total' => $fundingSourceQuery->count(),
                    'allocated' => (float) (clone $fundingSourceQuery)->sum('amount_allocated'),
                    'released' => (float) (clone $fundingSourceQuery)->sum('amount_released'),
                ],
                'external_supports' => [
                    'total' => $externalSupportQuery->count(),
                    'amount' => (float) (clone $externalSupportQuery)->sum('amount'),
                ],
            ],
        ]);
    }

    private function applyScope($query, ?User $user): void
    {
        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }
    }
}