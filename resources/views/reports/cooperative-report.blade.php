@php
$logoPath = realpath(public_path('SDN_Logo.png'));
if ($logoPath) {
    $logoPath = 'file:///' . str_replace('\\', '/', $logoPath);
}
$types = $cooperative->types->pluck('name')->join(', ') ?: 'N/A';
$latestLevel = $latestAccreditation->level ?? 'N/A';
$latestDate = $latestAccreditation->date_granted ? \Illuminate\Support\Carbon::parse($latestAccreditation->date_granted)->format('F d, Y') : 'N/A';
$printedAt = $generatedAt->format('F d, Y h:i A');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cooperative Report</title>
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
            padding-bottom: 18px;
            border-bottom: 1px solid #d1d5db;
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
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .brand-title .subtitle {
            font-size: 11px;
            color: #475569;
            margin: 0;
        }

        .report-meta {
            text-align: right;
        }

        .report-meta h1 {
            margin: 0;
            font-size: 21px;
            letter-spacing: 0.02em;
            color: #111827;
        }

        .report-meta p {
            margin: 4px 0 0;
            color: #475569;
            font-size: 11px;
        }

        .intro {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        .info-card {
            flex: 1;
            background: #f8fafc;
            padding: 16px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
        }

        .info-card h2 {
            margin: 0 0 10px;
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #475569;
        }

        .info-card p {
            margin: 0;
            line-height: 1.6;
            font-size: 11.5px;
            color: #334155;
        }

        .section-title {
            margin-top: 30px;
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: 600;
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
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }

        .details-table td.label {
            width: 28%;
            font-weight: 700;
            color: #334155;
            font-size: 12px;
            padding-top: 16px;
        }

        .details-table td.value {
            color: #475569;
            font-size: 11.5px;
        }

        .footer {
            margin-top: 26px;
            padding-top: 14px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            color: #64748b;
            text-align: center;
        }

        .section-notes {
            margin-top: 6px;
            font-size: 11px;
            color: #475569;
            line-height: 1.5;
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
                    <p class="subtitle">Cooperative Profile Report</p>
                </div>
            </div>
            <div class="report-meta">
                <h1>Cooperative Report</h1>
                <p>Printed on {{ $printedAt }}</p>
            </div>
        </div>

        <div class="intro">
            <div class="info-card">
                <h2>Cooperative</h2>
                <p><strong>{{ $cooperative->name }}</strong></p>
                <p>Reg. No. {{ $cooperative->registration_number }}</p>
                <p>Status: {{ $cooperative->status }}</p>
            </div>
            <div class="info-card">
                <h2>Context</h2>
                <p>Type(s): {{ $types }}</p>
                <p>Members: {{ $cooperative->members_count }}</p>
                <p>Established: {{ optional($cooperative->date_established)->format('F d, Y') ?? 'N/A' }}</p>
            </div>
        </div>

        <div class="section-title">Head Office</div>
        <table class="details-table">
            <tr>
                <td class="label">Address</td>
                <td class="value">{{ $cooperative->address }}, {{ $cooperative->city_municipality ?? 'N/A' }}, {{ $cooperative->province }}, {{ $cooperative->region ?? 'N/A' }} {{ $cooperative->barangay ? 'Brgy. ' . $cooperative->barangay : '' }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td class="value">{{ $cooperative->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Phone</td>
                <td class="value">{{ $cooperative->phone ?? 'N/A' }}</td>
            </tr>
        </table>

        <div class="section-title">Accreditation</div>
        <table class="details-table">
            <tr>
                <td class="label">Latest Level</td>
                <td class="value">{{ $latestLevel }}</td>
            </tr>
            <tr>
                <td class="label">Date Granted</td>
                <td class="value">{{ $latestDate }}</td>
            </tr>
        </table>

        <div class="section-title">System Details</div>
        <table class="details-table">
            <tr>
                <td class="label">Created</td>
                <td class="value">{{ optional($cooperative->created_at)->format('F d, Y h:i A') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Last Updated</td>
                <td class="value">{{ optional($cooperative->updated_at)->format('F d, Y h:i A') ?? 'N/A' }}</td>
            </tr>
        </table>

        <div class="footer">
            Report generated by PICTO COOP MGMT SYS • Confidential official cooperative record
        </div>
    </div>
</body>
</html>
