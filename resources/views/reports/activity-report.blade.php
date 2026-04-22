@php
$logoPath = realpath(public_path('SDN_Logo.png'));
if ($logoPath) {
    $logoPath = 'file:///' . str_replace('\\', '/', $logoPath);
}
$printedAt = $generatedAt->format('F d, Y h:i A');
$category = $activity->category ?: 'N/A';
$status = $activity->status ?: 'N/A';
$startDate = $activity->date_started ? \Illuminate\Support\Carbon::parse($activity->date_started)->format('F d, Y') : 'N/A';
$endDate = $activity->date_ended ? \Illuminate\Support\Carbon::parse($activity->date_ended)->format('F d, Y') : 'N/A';
$officerName = $activity->responsible_officer?->member?->full_name ?? 'N/A';
$budget = $activity->budget !== null ? '₱ ' . number_format((float) $activity->budget, 2) : 'N/A';
$actualExpense = $activity->actual_expense !== null ? '₱ ' . number_format((float) $activity->actual_expense, 2) : 'N/A';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        .page {
            padding: 28px 32px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 18px;
            margin-bottom: 24px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 12px;
            background: #f8fafc;
            padding: 8px;
        }

        .brand-title {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .brand-title .name {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .brand-title .subtitle {
            margin: 0;
            color: #475569;
            font-size: 11px;
        }

        .report-meta {
            text-align: right;
        }

        .report-meta h1 {
            margin: 0;
            font-size: 21px;
            color: #111827;
        }

        .report-meta p {
            margin: 4px 0 0;
            font-size: 11px;
            color: #475569;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
        }

        .summary-card h2 {
            margin: 0 0 10px;
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #475569;
        }

        .summary-card p {
            margin: 3px 0;
            font-size: 12px;
            color: #334155;
            line-height: 1.5;
        }

        .section-title {
            margin: 28px 0 8px;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .details-table td.label {
            width: 30%;
            color: #334155;
            font-weight: 700;
            font-size: 12px;
            padding-top: 16px;
        }

        .details-table td.value {
            color: #475569;
            font-size: 11.5px;
        }

        .details-block {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            margin-top: 6px;
            color: #334155;
            font-size: 11.5px;
            line-height: 1.7;
        }

        .footer {
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="brand">
                @if($logoPath)
                    <img src="{{ $logoPath }}" alt="SDN Logo">
                @endif
                <div class="brand-title">
                    <p class="name">SDN Cooperative Management Information System</p>
                    <p class="subtitle">Activity & Project Report</p>
                </div>
            </div>
            <div class="report-meta">
                <h1>Activity Report</h1>
                <p>Printed on {{ $printedAt }}</p>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <h2>Activity</h2>
                <p><strong>{{ $activity->title }}</strong></p>
                <p>Category: {{ $category }}</p>
                <p>Status: {{ $status }}</p>
            </div>
            <div class="summary-card">
                <h2>Timeline</h2>
                <p>Start date: {{ $startDate }}</p>
                <p>End date: {{ $endDate }}</p>
                <p>Responsible officer: {{ $officerName }}</p>
            </div>
            <div class="summary-card">
                <h2>Financials</h2>
                <p>Budget: {{ $budget }}</p>
                <p>Expense: {{ $actualExpense }}</p>
                <p>Funding source: {{ $activity->funding_source ?? 'N/A' }}</p>
            </div>
            <div class="summary-card">
                <h2>Attendance</h2>
                <p>Target members: {{ $activity->target_member_beneficiaries ?? 'N/A' }}</p>
                <p>Target community: {{ $activity->target_community_beneficiaries ?? 'N/A' }}</p>
                <p>Actual members: {{ $activity->actual_member_beneficiaries ?? 'N/A' }}</p>
                <p>Actual community: {{ $activity->actual_community_beneficiaries ?? 'N/A' }}</p>
            </div>
        </div>

        <div class="section-title">Detailed Description</div>
        <div class="details-block">
            <strong>Implementing Partner:</strong> {{ $activity->implementing_partner ?? 'N/A' }}<br>
            <strong>Cooperative:</strong> {{ $activity->cooperative->name }}<br>
            <strong>Created:</strong> {{ optional($activity->created_at)->format('F d, Y h:i A') ?? 'N/A' }}<br>
            <strong>Last Updated:</strong> {{ optional($activity->updated_at)->format('F d, Y h:i A') ?? 'N/A' }}
        </div>

        <div class="section-title">Outcomes</div>
        <div class="details-block">{{ $activity->outcomes ?? 'N/A' }}</div>

        <div class="section-title">Remarks</div>
        <div class="details-block">{{ $activity->remarks ?? 'N/A' }}</div>

        <div class="footer">
            Generated by PICTO COOP MGMT SYS • This document is intended for official cooperative reporting and submission.
        </div>
    </div>
</body>
</html>
