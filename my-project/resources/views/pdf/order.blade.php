<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Đơn hàng #{{ $order->id }}</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            font-size: 12px; 
            margin: 20px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px;
        }
        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }
        .order-info {
            margin-bottom: 20px;
        }
        .customer-info {
            margin-bottom: 20px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            border: 1px solid #000; 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background-color: #f0f0f0; 
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-confirmed { background-color: #d1ecf1; color: #0c5460; }
        .status-shipped { background-color: #d4edda; color: #155724; }
        .status-delivered { background-color: #c3e6cb; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SALEPHONEX</h1>
        <h2>ĐƠN HÀNG</h2>
    </div>

    <div class="company-info">
        <p><strong>Địa chỉ:</strong> 123 Đường ABC, Quận XYZ, TP.HCM</p>
        <p><strong>Điện thoại:</strong> 0123 456 789</p>
        <p><strong>Email:</strong> info@salephonex.com</p>
    </div>

    <div class="order-info">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; width: 50%;">
                    <p><strong>Mã đơn hàng:</strong> #{{ $order->id }}</p>
                    <p><strong>Ngày đặt:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Trạng thái:</strong> 
                        <span class="status-badge status-{{ $order->status }}">
                            @switch($order->status)
                                @case('pending')
                                    Chờ xác nhận
                                    @break
                                @case('confirmed')
                                    Đã xác nhận
                                    @break
                                @case('shipped')
                                    Đang giao hàng
                                    @break
                                @case('delivered')
                                    Đã giao hàng
                                    @break
                                @case('cancelled')
                                    Đã hủy
                                    @break
                                @default
                                    {{ $order->status }}
                            @endswitch
                        </span>
                    </p>
                    <p><strong>Phương thức thanh toán:</strong> 
                        @switch($order->payment_method)
                            @case('cod')
                                COD
                                @break
                            @case('vnpay')
                                VNPay
                                @break
                            @case('momo')
                                MoMo
                                @break
                            @case('cash')
                                Tiền mặt
                                @break
                            @default
                                {{ $order->payment_method }}
                        @endswitch
                    </p>
                </td>
                <td style="border: none; width: 50%;">
                    <p><strong>Khách hàng:</strong> {{ $order->user->name ?? 'Khách vãng lai' }}</p>
                    <p><strong>Người nhận:</strong> {{ $order->recipient_name }}</p>
                    <p><strong>Số điện thoại:</strong> {{ $order->phone }}</p>
                    <p><strong>Địa chỉ:</strong> {{ $order->address }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">STT</th>
                <th style="width: 50%;">Sản phẩm</th>
                <th style="width: 15%;">Đơn giá</th>
                <th style="width: 10%;">Số lượng</th>
                <th style="width: 20%;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($order->orderDetails as $i => $detail)
                @php
                    $price = 0;
                    $productName = 'Sản phẩm đã xóa';
                    
                    if ($detail->productVariant) {
                        $price = $detail->productVariant->price ?? 0;
                        $productName = $detail->productVariant->product->name ?? 'Sản phẩm đã xóa';
                        
                        // Thêm thông tin variant nếu có
                        if ($detail->productVariant->color || $detail->productVariant->size) {
                            $productName .= ' (';
                            if ($detail->productVariant->color) $productName .= $detail->productVariant->color;
                            if ($detail->productVariant->color && $detail->productVariant->size) $productName .= ' - ';
                            if ($detail->productVariant->size) $productName .= $detail->productVariant->size;
                            $productName .= ')';
                        }
                    } elseif ($detail->product) {
                        $price = $detail->product->price ?? 0;
                        $productName = $detail->product->name ?? 'Sản phẩm đã xóa';
                    }
                    
                    $subtotal = $detail->quantity * $price;
                    $total += $subtotal;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $productName }}</td>
                    <td style="text-align: right;">{{ number_format($price, 0, ',', '.') }} VND</td>
                    <td style="text-align: center;">{{ $detail->quantity }}</td>
                    <td style="text-align: right;">{{ number_format($subtotal, 0, ',', '.') }} VND</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;"><strong>TỔNG CỘNG:</strong></td>
                <td style="text-align: right;"><strong>{{ number_format($total, 0, ',', '.') }} VND</strong></td>
            </tr>
        </tfoot>
    </table>

    @if($order->note)
    <div style="margin-top: 20px;">
        <p><strong>Ghi chú:</strong> {{ $order->note }}</p>
    </div>
    @endif

    <div style="margin-top: 30px; text-align: center;">
        <p><em>Cảm ơn quý khách đã mua hàng!</em></p>
        <p><em>Mọi thắc mắc vui lòng liên hệ: 0123 456 789</em></p>
    </div>
</body>
</html>