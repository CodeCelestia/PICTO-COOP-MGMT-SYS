<?php

namespace App\Http\Controllers;

use App\Models\ActivityFundingSource;
use App\Models\FinancialRecord;
use App\Models\MemberLoan;
use App\Models\MemberSavings;
use App\Models\SavingsTransaction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class FinanceReportsController extends Controller
{
    public function statements(Request $request): Response
    {
        $records = $this->scopedFinancialRecords($request);

        $totals = [
            'income' => (float) (clone $records)->where('type', 'Income')->sum('amount'),
            'expense' => (float) (clone $records)->where('type', 'Expense')->sum('amount'),
            'grants' => (float) (clone $records)->where('type', 'Grant')->sum('amount'),
            'loans' => (float) (clone $records)->where('type', 'Loan')->sum('amount'),
            'assets' => (float) (clone $records)->sum('total_assets'),
            'liabilities' => (float) (clone $records)->sum('total_liabilities'),
            'net_surplus' => (float) (clone $records)->sum('net_surplus'),
        ];

        return Inertia::render('Finance/Reports/Statements', [
            'totals' => $totals,
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function loanPortfolio(Request $request): Response
    {
        $query = MemberLoan::query();
        $this->applyScope($query, $request);

        $summary = [
            'total_loans' => $query->count(),
            'pending' => (clone $query)->where('status', 'Pending')->count(),
            'active' => (clone $query)->where('status', 'Active')->count(),
            'completed' => (clone $query)->where('status', 'Completed')->count(),
            'defaulted' => (clone $query)->where('status', 'Defaulted')->count(),
            'principal_total' => (float) (clone $query)->sum('principal'),
            'outstanding_balance' => (float) (clone $query)->whereIn('status', ['Active', 'Approved', 'Pending'])->sum('principal'),
        ];

        return Inertia::render('Finance/Reports/LoanPortfolio', [
            'summary' => $summary,
        ]);
    }

    public function savingsSummary(Request $request): Response
    {
        $savingsQuery = MemberSavings::query();
        $this->applyScope($savingsQuery, $request);

        $transactionsQuery = SavingsTransaction::query();
        $this->applyScope($transactionsQuery, $request);

        $summary = [
            'total_accounts' => $savingsQuery->count(),
            'active_accounts' => (clone $savingsQuery)->where('account_status', 'Active')->count(),
            'total_balance' => (float) (clone $savingsQuery)->sum('current_balance'),
            'total_deposits' => (float) (clone $transactionsQuery)->where('type', 'Deposit')->sum('amount'),
            'total_withdrawals' => (float) (clone $transactionsQuery)->where('type', 'Withdrawal')->sum('amount'),
            'total_interest_credited' => (float) (clone $transactionsQuery)->where('type', 'Interest')->sum('amount'),
        ];

        return Inertia::render('Finance/Reports/SavingsSummary', [
            'summary' => $summary,
        ]);
    }

    public function funderAccountability(Request $request): Response
    {
        $query = ActivityFundingSource::query();
        $this->applyScope($query, $request);

        return Inertia::render('Finance/Reports/Statements', [
            'totals' => [
                'funders' => $query->count(),
                'allocated' => (float) (clone $query)->sum('amount_allocated'),
                'released' => (float) (clone $query)->sum('amount_released'),
            ],
            'filters' => $request->only(['from', 'to']),
            'mode' => 'funder-accountability',
        ]);
    }

    public function trends(Request $request): Response
    {
        $query = $this->scopedFinancialRecords($request);

        $trendRows = (clone $query)
            ->selectRaw('period, SUM(COALESCE(amount, 0)) as total_amount')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return Inertia::render('Finance/Reports/Statements', [
            'totals' => [
                'trend_rows' => $trendRows,
            ],
            'filters' => $request->only(['from', 'to']),
            'mode' => 'trends',
        ]);
    }

    public function exportStatements(Request $request): StreamedResponse
    {
        $records = $this->scopedFinancialRecords($request)->get(['period', 'type', 'amount', 'date_recorded']);

        return $this->streamCsv('finance-statements.csv', [
            'Period', 'Type', 'Amount', 'Date Recorded',
        ], $records->map(function ($row) {
            return [
                $row->period,
                $row->type,
                $row->amount,
                $row->date_recorded,
            ];
        })->all());
    }

    public function exportLoanPortfolio(Request $request): StreamedResponse
    {
        $query = MemberLoan::query();
        $this->applyScope($query, $request);

        $rows = $query->get(['id', 'member_id', 'principal', 'interest_rate', 'term_months', 'status']);

        return $this->streamCsv('loan-portfolio.csv', [
            'Loan ID', 'Member ID', 'Principal', 'Interest Rate', 'Term Months', 'Status',
        ], $rows->map(function ($row) {
            return [
                $row->id,
                $row->member_id,
                $row->principal,
                $row->interest_rate,
                $row->term_months,
                $row->status,
            ];
        })->all());
    }

    public function exportSavingsSummary(Request $request): StreamedResponse
    {
        $query = MemberSavings::query();
        $this->applyScope($query, $request);

        $rows = $query->get(['account_number', 'member_id', 'account_status', 'current_balance', 'interest_rate']);

        return $this->streamCsv('savings-summary.csv', [
            'Account Number', 'Member ID', 'Status', 'Balance', 'Interest Rate',
        ], $rows->map(function ($row) {
            return [
                $row->account_number,
                $row->member_id,
                $row->account_status,
                $row->current_balance,
                $row->interest_rate,
            ];
        })->all());
    }

    public function exportFunderAccountability(Request $request): StreamedResponse
    {
        $query = ActivityFundingSource::query();
        $this->applyScope($query, $request);

        $rows = $query->get(['funder_name', 'funder_type', 'amount_allocated', 'amount_released', 'status']);

        return $this->streamCsv('funder-accountability.csv', [
            'Funder', 'Type', 'Allocated', 'Released', 'Status',
        ], $rows->map(function ($row) {
            return [
                $row->funder_name,
                $row->funder_type,
                $row->amount_allocated,
                $row->amount_released,
                $row->status,
            ];
        })->all());
    }

    public function exportTrends(Request $request): StreamedResponse
    {
        $query = $this->scopedFinancialRecords($request)
            ->selectRaw('period, SUM(COALESCE(amount, 0)) as total_amount')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $this->streamCsv('finance-trends.csv', ['Period', 'Total Amount'], $query->map(function ($row) {
            return [$row->period, $row->total_amount];
        })->all());
    }

    private function scopedFinancialRecords(Request $request)
    {
        $query = FinancialRecord::query();
        $this->applyScope($query, $request);

        if ($request->filled('from')) {
            $query->whereDate('date_recorded', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('date_recorded', '<=', $request->input('to'));
        }

        return $query;
    }

    private function applyScope($query, Request $request): void
    {
        $user = $request->user();

        if ($user && ! $user->can('view-all-cooperatives') && $user->coop_id) {
            $query->where('coop_id', $user->coop_id);
        }
    }

    /**
     * @param array<int, string> $header
     * @param array<int, array<int, mixed>> $rows
     */
    private function streamCsv(string $fileName, array $header, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
