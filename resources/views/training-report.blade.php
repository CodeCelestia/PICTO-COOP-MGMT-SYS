@php
$logoPath = realpath(public_path('SDN_Logo.png'));
if ($logoPath) {
    $logoPath = 'file:///' . str_replace('\\', '/', $logoPath);
}

$printedAt = $generatedAt->format('F d, Y h:i A');
$dateConducted = $training->date_conducted
    ? \Illuminate\Support\Carbon::parse($training->date_conducted)->format('F d, Y')
    : 'N/A';
$facilitator = $training->facilitator ?: 'N/A';
$venue = $training->venue ?: 'N/A';
$targetGroup = $training->target_group ?: 'N/A';
$skillsTargeted = $training->skills_targeted ?: 'N/A';
$status = $training->status ?: 'N/A';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Training Report</title>
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

        .list-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .list-table th,
        .list-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            font-size: 11px;
            text-align: left;
            vertical-align: top;
        }

        .list-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .muted {
            color: #64748b;
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
                    <p class="subtitle">Training & Capacity Building Report</p>
                </div>
            </div>
            <div class="report-meta">
                <h1>Training Report</h1>
                <p>Printed on {{ $printedAt }}</p>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <h2>Training</h2>
                <p><strong>{{ $training->title }}</strong></p>
                <p>Status: {{ $status }}</p>
                <p>Date conducted: {{ $dateConducted }}</p>
            </div>
            <div class="summary-card">
                <h2>Delivery</h2>
                <p>Facilitator: {{ $facilitator }}</p>
                <p>Venue: {{ $venue }}</p>
                <p>Target group: {{ $targetGroup }}</p>
            </div>
            <div class="summary-card">
                <h2>Coverage</h2>
                <p>Cooperatives participating: {{ $cooperatives->count() }}</p>
                <p>Total linked records: {{ $trainingRecords->count() }}</p>
                <p>Total participants: {{ $participants->count() }}</p>
            </div>
            <div class="summary-card">
                <h2>Follow Up</h2>
                <p>Needed: {{ $training->follow_up_needed ? 'Yes' : 'No' }}</p>
                <p>Date: {{ optional($training->follow_up_date)->format('F d, Y') ?? 'N/A' }}</p>
                <p>Remarks: {{ $training->follow_up_remarks ?: 'N/A' }}</p>
            </div>
        </div>

        <div class="section-title">Skills Targeted</div>
        <div class="details-block">{{ $skillsTargeted }}</div>

        <div class="section-title">Cooperatives Participating</div>
        <table class="list-table">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th style="width: 34%;">Cooperative</th>
                    <th style="width: 20%;">Registration No.</th>
                    <th style="width: 20%;">Classification</th>
                    <th style="width: 20%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cooperatives as $index => $cooperative)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $cooperative->name }}</td>
                        <td>{{ $cooperative->registration_number ?: 'N/A' }}</td>
                        <td>{{ $cooperative->classification ?: 'N/A' }}</td>
                        <td>{{ $cooperative->status ?: 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">No participating cooperatives recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">Participants</div>
        <table class="list-table">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th style="width: 30%;">Participant</th>
                    <th style="width: 24%;">Cooperative</th>
                    <th style="width: 15%;">Outcome</th>
                    <th style="width: 12%;">Certificate No.</th>
                    <th style="width: 13%;">Certificate Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $index => $participant)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $participant['name'] ?? 'Unknown member' }}</td>
                        <td>{{ $participant['cooperative'] ?? 'N/A' }}</td>
                        <td>{{ $participant['outcome'] ?? 'N/A' }}</td>
                        <td>{{ $participant['certificate_no'] ?? '' }}</td>
                        <td>{{ $participant['certificate_date'] ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No participants recorded for this training.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            Generated by PICTO COOP MGMT SYS • This document is intended for official cooperative reporting and submission.
        </div>
    </div>
</body>
</html>
