<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hóa đơn thanh toán</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.6;
            background: #f9fafb;
            margin: 0;
            padding: 20px;
        }

        .invoice-wrapper {
            max-width: 700px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .invoice-header {
            background: linear-gradient(135deg, #dc2626, #f87171);
            color: white;
            text-align: center;
            padding: 20px;
        }

        .invoice-header h2 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 1px;
        }

        .invoice-body {
            padding: 25px 30px;
        }

        .info-box {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .info-box p {
            margin: 5px 0;
            font-size: 14px;
            color: #374151;
        }

        h3 {
            margin-top: 25px;
            color: #dc2626;
            font-size: 18px;
            border-bottom: 2px solid #f87171;
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table, th, td {
            border: 1px solid #e5e7eb;
        }

        th {
            background-color: #dc2626;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }

        td {
            padding: 10px;
            font-size: 14px;
            color: #374151;
        }

        .total {
            text-align: right;
            font-weight: bold;
            font-size: 18px;
            color: #dc2626;
            margin-top: 20px;
        }

        .footer {
            background: #f9fafb;
            padding: 20px;
            font-size: 13px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="invoice-wrapper">
        <div class="invoice-header">
            <h2>Hóa đơn thanh toán #{{ $order->id }}</h2>
        </div>

        <div class="invoice-body">
            <h3>Thông tin khách hàng</h3>
            <div class="info-box">
                <p><strong>Khách hàng:</strong> {{ $order->user->name ?? 'Khách hàng' }}</p>
                <p><strong>Email:</strong> {{ $order->user->email ?? 'Không có email' }}</p>
                <p><strong>Ngày đặt:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>

            <h3>Chi tiết đơn hàng</h3>
            <table>
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderDetails as $detail)
                        <tr>
                            <td>{{ $detail->productName }}</td>
                            <td>{{ $detail->quantity }}</td>
                            <td>{{ number_format($detail->price, 0, ',', '.') }}₫</td>
                            <td>{{ number_format($detail->subtotal, 0, ',', '.') }}₫</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="total">
                Tổng tiền: {{ number_format($order->total_amount ?? 0, 0, ',', '.') }}₫
            </p>
        </div>

        <div class="footer">
            <p>Xin cảm ơn,<br/>Đội ngũ {{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>
