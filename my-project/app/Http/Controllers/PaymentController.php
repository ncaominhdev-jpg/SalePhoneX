<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    // Lấy danh sách payment
    public function index()
    {
        return response()->json(Payment::all());
    }

    // Tạo mới payment (dùng cho COD)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'user_id' => 'required|integer|exists:users,id',
            'method' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'paid_at' => 'nullable|date',
        ]);

        $payment = Payment::create([
            'order_id' => $validated['order_id'],
            'user_id' => $validated['user_id'],
            'method' => $validated['method'],
            'amount' => $validated['amount'],
            'status' => $validated['method'] === 'cod' ? 'pending' : 'completed',
            'paid_at' => $validated['method'] === 'cod' ? null : now(),
        ]);

        return response()->json($payment, 201);
    }

    // Xem chi tiết payment
    public function show($id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($payment);
    }

    // Cập nhật payment
    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'order_id' => 'sometimes|integer|exists:orders,id',
            'user_id' => 'sometimes|integer|exists:users,id',
            'method' => 'sometimes|string|max:50',
            'amount' => 'sometimes|numeric|min:0',
            'paid_at' => 'nullable|date',
        ]);

        $payment->update($validated);
        return response()->json($payment);
    }

    // Xóa payment
    public function destroy($id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $payment->delete();
        return response()->json(['message' => 'Deleted']);
    }

    //cod
    public function codPayment(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'cart_ids' => 'required|array',
            'cart_ids.*' => 'integer|exists:carts,id',
        ]);

        // Tạo bản ghi payment cho COD
        $payment = Payment::create([
            'order_id' => $validated['order_id'],
            'user_id' => $validated['user_id'],
            'method' => 'cod',
            'amount' => $validated['amount'],
            'status' => 'pending', // COD => chưa thanh toán
            'paid_at' => null,
        ]);

        $order = Order::find($validated['order_id']);

        if ($order) {
            $order->update([
                'status' => 'pending',         // Đơn hàng đang chờ xử lý
                'payment_method' => 'cod',
            ]);

            // Xóa giỏ hàng của user sau khi đặt COD
            DB::table('carts')->whereIn('id', $validated['cart_ids'])->delete();

            // Redirect sang trang cảm ơn riêng cho COD
            return redirect("http://localhost:5173/thank-you-cod?status=success&order_id={$order->id}");
        }

        return redirect("http://localhost:5173/thank-you-cod?status=fail");
    }





    // 🔹 Tạo thanh toán VNPAY
    public function createVnpayPayment(Request $request)
    {
        $data = $request->all();
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'user_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'cart_ids' => 'required|array',
            'cart_ids.*' => 'integer|exists:carts,id',

        ]);

        if ($data['user_id'] != $user->id) {
            return response()->json(['error' => 'User ID không hợp lệ'], 422);
        }
        // 👉 Lưu cart_ids vào session để callback xài lại
        session(['cart_ids' => $data['cart_ids']]);

        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl =  "http://localhost:8000/api/payments/vnpay/return?cart_ids=" . implode(',', $data['cart_ids']);
        $vnp_TmnCode = "1VYBIYQP";
        $vnp_HashSecret = "NOH6MBGNLQL9O9OMMFMZ2AX8NIEP50W1";

        $vnp_TxnRef = $data['order_id'];
        $vnp_Amount = $data['amount'] * 100;

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $_SERVER['REMOTE_ADDR'],
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => "Thanh toán đơn hàng",
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_BankCode" => "NCB",
        ];

        ksort($inputData);
        $query = http_build_query($inputData);
        $hashdata = $query;
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $paymentUrl = $vnp_Url . "?" . $query . '&vnp_SecureHash=' . $vnpSecureHash;

        // 👉 Lưu payment (status = pending)
        Payment::create([
            'order_id' => $data['order_id'],
            'user_id' => $user->id,
            'method' => 'vnpay',
            'amount' => $data['amount'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'payment_url' => $paymentUrl,
            'transaction_ref' => $vnp_TxnRef
        ]);
    }

    // VNPay Return callback
    public function vnpayReturn(Request $request)
    {
        Log::info("VNPay Return called", $request->all());
        $data = $request->all();
        $responseCode = $data['vnp_ResponseCode'] ?? '99';

        $txnRef = $data['vnp_TxnRef'] ?? null;

        if (!$txnRef) {
            return redirect("http://localhost:5173/cam-on-quy-khach?status=fail");
        }

        $payment = Payment::where('order_id', $txnRef)->first();

        if ($payment) {
            $payment->update([
                'status' => $responseCode === '00' ? 'completed' : 'failed',
                'paid_at' => now(),
            ]);

            if ($responseCode === '00') {
                $order = Order::with(['orderItems.productVariant.product', 'user'])
                    ->find($payment->order_id);

                if ($order) {
                    // Cập nhật status thành 'confirmed' khi thanh toán thành công
                    $order->update([
                        'status' => 'confirmed',
                        'payment_method' => 'vnpay',
                        'inventory_decreased' => true,
                    ]);

                    // Trừ hàng tồn kho khi thanh toán thành công
                    \App\Services\InventoryService::decreaseOrderInventory($order);

                    // Xóa giỏ theo cart_ids từ session hoặc DB
                    $cartIds = explode(',', $request->query('cart_ids', ''));
                    $cartIds = array_filter($cartIds);

                    if (!empty($cartIds)) {
                        DB::table('carts')->whereIn('id', $cartIds)->delete();
                    }

                    return redirect("http://localhost:5173/cam-on-quy-khach?status=success&order_id={$order->id}");
                }
            }

            return redirect("http://localhost:5173/cam-on-quy-khach?status=fail");
        }

        return redirect("http://localhost:5173/cam-on-quy-khach?status=fail");
    }

    public function momoReturn(Request $request)
    {
        Log::info("MoMo Return called", $request->all());

        $orderIdRaw = $request->input('orderId');
        $realOrderId = explode('-', $orderIdRaw)[0] ?? null;
        $resultCode = $request->input('resultCode');

        $payment = Payment::where('order_id', $realOrderId)->first();

        if ($payment) {
            $payment->update([
                'status' => $resultCode == 0 ? 'completed' : 'failed',
                'paid_at' => now(),
            ]);

            if ($resultCode == 0) {
                $order = Order::with(['orderItems.productVariant.product', 'user'])
                    ->find($payment->order_id);

                if ($order) {
                    // Cập nhật status thành 'confirmed' khi thanh toán thành công
                    $order->update([
                        'status' => 'confirmed',
                        'payment_method' => 'momo',
                        'inventory_decreased' => true,

                    ]);

                    // Trừ hàng tồn kho khi thanh toán thành công
                    \App\Services\InventoryService::decreaseOrderInventory($order);

                    // Xóa giỏ theo cart_ids
                    $cartIds = explode(',', $request->query('cart_ids', ''));
                    $cartIds = array_filter($cartIds);

                    if (!empty($cartIds)) {
                        DB::table('carts')->whereIn('id', $cartIds)->delete();
                    }

                    return redirect("http://localhost:5173/cam-on-quy-khach?status=success&order_id={$order->id}");
                }
            }
            return redirect("http://localhost:5173/cam-on-quy-khach?status=fail");
        }

        return redirect("http://localhost:5173/cam-on-quy-khach?status=fail");
    }


    // 🔹 MoMo Payment
    public function momopayment(Request $request)
    {
        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";

        $partnerCode = 'MOMOBKUN20180529';
        $accessKey = 'klm05TvNBzhg7h7j';
        $secretKey = 'at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa';

        $amount = $request->input('amount');
        $realOrderId = $request->input('order_id');
        $cartIds = $request->input('cart_ids', []);
        $orderId = $realOrderId . '-' . uniqid(); // MoMo yêu cầu unique
        $requestId = uniqid();
        $redirectUrl = "http://localhost:8000/api/momo-return?cart_ids=" . implode(',', $request->input('cart_ids', []));
        $ipnUrl = "http://localhost:8000/api/momo-ipn";

        $rawHash = "accessKey=$accessKey&amount=$amount&extraData=&ipnUrl=$ipnUrl&orderId=$orderId&orderInfo=Thanh toán MoMo&partnerCode=$partnerCode&redirectUrl=$redirectUrl&requestId=$requestId&requestType=payWithATM";
        $signature = hash_hmac("sha256", $rawHash, $secretKey);
        session(['cart_ids' => $cartIds]);
        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => "MoMo Payment",
            'storeId' => "MomoTestStore",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => "Thanh toán MoMo",
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => '',
            'requestType' => "payWithATM",
            'signature' => $signature
        ];

        $response = Http::withOptions(['verify' => false])
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($endpoint, $data);

        $result = $response->json();

        // 👉 Lưu payment (pending)
        Payment::create([
            'order_id' => $realOrderId,   // order thật
            'user_id' => $request->input('user_id'),
            'method' => 'momo',
            'amount' => $amount,
            'status' => 'pending',
            'transaction_ref' => $orderId // lưu thêm cho biết phiên thanh toán MoMo
        ]);


        return response()->json([
            'payUrl' => $result['payUrl'] ?? null,
            'orderId' => $orderId,
            'message' => $result['message'] ?? 'Có lỗi xảy ra'
        ]);
    }


    // public function momoReturn(Request $request)
    // {
    //     Log::info("MoMo Return called", $request->all());

    //     $orderIdRaw = $request->input('orderId');
    //     $realOrderId = explode('-', $orderIdRaw)[0] ?? null;
    //     $resultCode = $request->input('resultCode');

    //     $payment = Payment::where('order_id', $realOrderId)->first();

    //     if ($payment) {
    //         $payment->update([
    //             'status' => $resultCode == 0 ? 'completed' : 'failed',
    //             'paid_at' => now(),
    //         ]);

    //         if ($resultCode == 0) {
    //             // 👉 Lấy thông tin đơn hàng và các quan hệ
    //             $order = Order::with(['orderItems.productVariant.product', 'user'])
    //                 ->find($payment->order_id);

    //             if ($order) {
    //                 // Kiểm tra validation trạng thái
    //                 $statusOrder = [
    //                     'pending' => 1,
    //                     'confirmed' => 2,
    //                     'shipped' => 3,
    //                     'delivered' => 4,
    //                     'cancelled' => 5
    //                 ];

    //                 // Chỉ cho phép cập nhật nếu trạng thái hiện tại là pending
    //                 if ($order->status === 'pending') {
    //                     $order->update([
    //                         'status' => 'confirmed',
    //                         'payment_method' => 'momo',
    //                     ]);
    //                 } else {
    //                     Log::warning("MoMo: Cannot update order status", [
    //                         'order_id' => $order->id,
    //                         'current_status' => $order->status,
    //                         'requested_status' => 'confirmed'
    //                     ]);
    //                 }

    //                 // Xóa giỏ theo cart_ids
    //                 $cartIds = explode(',', $request->query('cart_ids', ''));
    //                 $cartIds = array_filter($cartIds);

    //                 if (!empty($cartIds)) {
    //                     DB::table('carts')->whereIn('id', $cartIds)->delete();
    //                 }


    //                 return redirect("http://localhost:5173/cam-on-quy-khach?status=success&order_id={$order->id}");
    //             }
    //         }
    //         return redirect("http://localhost:5173/cam-on-quy-khach?status=fail");
    //     }

    //     return redirect("http://localhost:5173/cam-on-quy-khach?status=fail");
    // }
}
