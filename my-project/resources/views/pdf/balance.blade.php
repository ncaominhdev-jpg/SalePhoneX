<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Phiếu điều chỉnh #{{ $balance->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h2>Phiếu điều chỉnh tồn kho #{{ $balance->id }}</h2>
    <p><strong>Kho:</strong> {{ $balance->audit->warehouse->name ?? '-' }}</p>
    <p><strong>Ngày:</strong> {{ $balance->created_at->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>SL hệ thống</th>
                <th>SL thực tế</th>
                <th>SL điều chỉnh</th>
                <th>Lý do</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($balance->details as $item)
                <tr>
                    <td>{{ $item->productVariant->product->name ?? '' }} - {{ $item->productVariant->variant_name ?? '' }}</td>
                    <td>{{ $item->recorded_quantity }}</td>
                    <td>{{ $item->actual_quantity }}</td>
                    <td>{{ $item->adjusted_quantity }}</td>
                    <td>{{ $item->reason }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
