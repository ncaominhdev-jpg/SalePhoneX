<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Audits Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1, h2 {
            text-align: center;
        }
        .audit-section {
            margin-bottom: 40px;
            page-break-after: always;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #333;
        }
        th, td {
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
        .header-info {
            margin-bottom: 10px;
        }
        .header-info div {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <h1>Audits Report</h1>
    @foreach ($audits as $audit)
        <div class="audit-section">
            <h2>Audit #{{ $audit->id }}</h2>
            <div class="header-info">
                <div><strong>Warehouse:</strong> {{ $audit->warehouse->name ?? 'N/A' }}</div>
                <div><strong>Audit Date:</strong> {{ \Illuminate\Support\Carbon::parse($audit->audit_date)->format('Y-m-d') }}</div>
                <div><strong>Note:</strong> {{ $audit->note ?? '' }}</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Recorded Quantity</th>
                        <th>Actual Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($audit->reports as $report)
                        <tr>
                            <td>{{ $report->productVariant->name ?? 'N/A' }}</td>
                            <td>{{ $report->recorded_quantity }}</td>
                            <td>{{ $report->actual_quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
