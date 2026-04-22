<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Phiếu xuất kho</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        h2 { text-align: center; }
    </style>
</head>
<body>
    <h2>PHIẾU XUẤT KHO</h2>
    <p><strong>Mã phiếu:</strong> {{ $export->id }}</p>
    <p><strong>Ngày xuất:</strong> {{ $export->export_date->format('d/m/Y') }}</p>
    <p><strong>Kho xuất:</strong> {{ $export->fromWarehouse->name }}</p>
    @if($export->toWarehouse)
        <p><strong>Kho nhận:</strong> {{ $export->toWarehouse->name }}</p>
    @endif
    @if($export->order_id)
        <p><strong>Đơn hàng liên quan:</strong> {{ $export->order_id }}</p>
    @endif
    <p><strong>Người tạo:</strong> {{ $export->user->name }}</p>
    <p><strong>Ghi chú:</strong> {{ $export->note }}</p>

    <table>
        <thead>
            <tr>
                <th>STT</th>
                <th>Sản phẩm</th>
                <th>Số lượng</th>
            </tr>
        </thead>
        <tbody>
            @foreach($export->exportDetails as $i => $detail)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $detail->productVariant->name }}</td>
                    <td>{{ $detail->quantity }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
