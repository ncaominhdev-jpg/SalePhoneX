<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Giao dịch kho #{{ $transaction->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width:100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        .header { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>Phiếu giao dịch kho #{{ $transaction->id }}</h2>

    <div>
        <strong>Loại:</strong> {{ $transaction->type }}<br>
        <strong>Người tạo:</strong> {{ $transaction->creator?->name ?? '-' }}<br>
        <strong>Ngày:</strong> {{ $transaction->created_at->format('d/m/Y H:i') }}<br>
    </div>

    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Biến thể</th>
                <th>SL trước</th>
                <th>SL sau</th>
                <th>Thay đổi</th>
                <th>Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $transaction->inventory->productVariant->product->name ?? ($transaction->inventory->productVariant->name ?? '-') }}</td>
                <td>{{ $transaction->inventory->productVariant->name ?? '-' }}</td>
                <td>{{ $transaction->quantity_before }}</td>
                <td>{{ $transaction->quantity_after }}</td>
                <td>{{ $transaction->quantity_change > 0 ? '+' . $transaction->quantity_change : $transaction->quantity_change }}</td>
                <td>{{ $transaction->note ?? '-' }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
