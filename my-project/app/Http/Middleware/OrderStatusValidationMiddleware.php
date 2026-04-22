<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Order;

class OrderStatusValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Chỉ áp dụng cho các request cập nhật đơn hàng
        if ($request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $orderId = $request->route('order');
            
            if ($orderId) {
                $order = Order::find($orderId);
                
                if ($order && $request->has('status')) {
                    $newStatus = $request->input('status');
                    
                    // Định nghĩa thứ tự trạng thái hợp lệ
                    $statusOrder = [
                        'pending' => 1,
                        'confirmed' => 2,
                        'shipped' => 3,
                        'delivered' => 4,
                        'cancelled' => 5
                    ];

                    // Cho phép hủy đơn từ bất kỳ trạng thái nào
                    if ($newStatus === 'cancelled') {
                        return $next($request);
                    }

                    // Kiểm tra nếu đang cố lùi trạng thái
                    if (isset($statusOrder[$order->status]) && isset($statusOrder[$newStatus])) {
                        $currentStatusValue = $statusOrder[$order->status];
                        $newStatusValue = $statusOrder[$newStatus];

                        if ($newStatusValue < $currentStatusValue) {
                            return response()->json([
                                'message' => 'Không được phép lùi trạng thái',
                                'current_status' => $order->status,
                                'requested_status' => $newStatus
                            ], 400);
                        }
                    }
                }
            }
        }

        return $next($request);
    }
} 