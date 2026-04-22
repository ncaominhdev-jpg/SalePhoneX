<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Audit Report #{{ $audit->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1, h2 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #333;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #eee;
        }
        .header-info {
            margin-bottom: 20px;
        }
        .header-info div {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <h1>Chi tiết phiếu kiểm #{{ $audit->id }}</h1>
    <div class="header-info">
        <div><strong>Chi nhánh:</strong> {{ $audit->warehouse->name ?? 'N/A' }}</div>
        <div><strong>ngày kiểm:</strong> {{ \Illuminate\Support\Carbon::parse($audit->audit_date)->format('Y-m-d') }}</div>
        <div><strong>Note:</strong> {{ $audit->note ?? '' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Số lượng kho</th>
                <th>Điều chỉnh</th>
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
</body>
</html>
