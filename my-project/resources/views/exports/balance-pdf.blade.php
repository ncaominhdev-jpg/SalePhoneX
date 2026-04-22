<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Phiếu điều chỉnh tồn kho</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: left; }
        th { background-color: #eee; }
    </style>
</head>
<body>
    <h2>Phiếu điều chỉnh tồn kho #{{ $balance->id }}</h2>
    <p><strong>Kho:</strong> {{ $balance->audit->warehouse->name ?? '' }}</p>
    <p><strong>Phiếu kiểm kê:</strong> #{{ $balance->audit_id }}</p>
    <p><strong>Người tạo:</strong> {{ $balance->audit->user->name ?? 'N/A' }}</p>
    <p><strong>Ngày tạo:</strong> {{ $balance->created_at->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Sản phẩm</th>
                <th>SL hệ thống</th>
                <th>SL thực tế</th>
                <th>SL điều chỉnh</th>
                <th>Lý do</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($balance->details as $i => $detail)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $detail->productVariant->product->name ?? '' }} - {{ $detail->productVariant->variant_name }}</td>
                    <td style=\"text-align: center\">{{ $detail->recorded_quantity }}</td>
                    <td style=\"text-align: center\">{{ $detail->actual_quantity }}</td>
                    <td style=\"text-align: center\">{{ $detail->adjusted_quantity }}</td>
                    <td>{{ $detail->reason }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
