<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Phiếu kiểm kho</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        h2, h3 { margin: 0; padding: 0; }
    </style>
</head>
<body>

<h2>PHIẾU KIỂM KÊ KHO</h2>
<p><strong>Mã phiếu:</strong> {{ $audit->id }}</p>
<p><strong>Kho:</strong> {{ $audit->warehouse->name }}</p>
<p><strong>Ngày kiểm:</strong> {{ $audit->audit_date->format('d/m/Y') }}</p>
<p><strong>Người tạo:</strong> {{ $audit->creator->name ?? '---' }}</p>
<p><strong>Ghi chú:</strong> {{ $audit->note }}</p>

<h3>Chi tiết kiểm kho</h3>
<table>
    <thead>
        <tr>
            <th>STT</th>
            <th>Sản phẩm</th>
            <th>Tồn kho</th>
            <th>Thực tế</th>
            <th>Chênh lệch</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($audit->reports as $index => $report)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $report->productVariant->name ?? '---' }}</td>
                <td>{{ $report->recorded_quantity }}</td>
                <td>{{ $report->actual_quantity }}</td>
                <td>{{ $report->actual_quantity - $report->recorded_quantity }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>Điều chỉnh tồn kho</h3>
@if ($audit->balances->count())
<table>
    <thead>
        <tr>
            <th>STT</th>
            <th>Sản phẩm</th>
            <th>Số lượng sau điều chỉnh</th>
            <th>Lý do</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($audit->balances as $index => $balance)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $balance->productVariant->name ?? '---' }}</td>
                <td>{{ $balance->adjusted_quantity }}</td>
                <td>{{ $balance->reason }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@else
<p><em>Không có điều chỉnh tồn kho.</em></p>
@endif

</body>
</html>
