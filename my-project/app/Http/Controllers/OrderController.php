<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\UserVoucher;
use App\Models\Voucher;
use App\Models\Branch;              // ✅ thêm
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\InvoiceMail;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function generatePdf($id)
    {
        $order = Order::with(['orderDetails.productVariant.product', 'user'])->findOrFail($id);
        $pdf = Pdf::loadView('pdf.order', compact('order'));
        return $pdf->download('don-hang-' . $order->id . '.pdf');
    }

    public function viewPdf($id)
    {
        $order = Order::with(['orderDetails.productVariant.product', 'user'])->findOrFail($id);
        $pdf = Pdf::loadView('pdf.order', compact('order'));
        return $pdf->stream('don-hang-' . $order->id . '.pdf');
    }

    public function index()
    {
        $orders = Order::with('orderDetails')->get();
        return response()->json($orders);
    }

    // =========================
    // TẠO MỚI ĐƠN HÀNG (UPDATED)
    // =========================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'                   => 'required|integer|exists:users,id',
            // ✅ branch_id để nullable; sẽ kiểm tra theo delivery_type bên dưới
            'branch_id'                 => 'nullable|integer|exists:branches,id',
            // ✅ thêm delivery_type
            'delivery_type'             => 'required|in:store,home',
            // ✅ city để pick chi nhánh khi giao tận nơi
            'shipping_city'             => 'nullable|string|max:255',

            'total_amount'              => 'required|numeric|min:0',
            'payment_method'            => 'required|string|max:50',
            'note'                      => 'nullable|string',
            'recipient_name'            => 'required|string|max:255',
            'phone'                     => 'required|string|max:20',
            'address'                   => 'required|string|max:255',

            'order_details'                         => 'required|array|min:1',
            'order_details.*.product_variant_id'    => 'required|integer',
            'order_details.*.quantity'              => 'required|integer|min:1',

            'voucher_code'              => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $discount = 0;

            // --- Voucher (giữ nguyên) ---
            if (!empty($validated['voucher_code'])) {
                $voucher = Voucher::where('code', $validated['voucher_code'])->first();

                if ($voucher) {
                    $userVoucher = UserVoucher::where('user_id', $validated['user_id'])
                        ->where('voucher_id', $voucher->id)
                        ->where('is_used', false)
                        ->first();

                    if ($userVoucher && $voucher->status == 1 && now()->between($voucher->start_date, $voucher->end_date)) {
                        $discount = $voucher->type === 'percent'
                            ? $validated['total_amount'] * ($voucher->value / 100)
                            : $voucher->value;

                        // Đánh dấu voucher đã sử dụng (tạm)
                        $userVoucher->update(['is_used' => true]);
                    }
                }
            }

            // --- QUYẾT ĐỊNH CHI NHÁNH THEO delivery_type ---
            $branchId = null;

            if ($validated['delivery_type'] === 'store') {
                // Khách nhận tại cửa hàng → cần branch_id & phải đủ tồn
                if (empty($validated['branch_id'])) {
                    throw new \Exception('Thiếu chi nhánh cho đơn nhận tại cửa hàng');
                }
                $branch = Branch::where('id', $validated['branch_id'])->where('status', 1)->first();
                if (!$branch) {
                    throw new \Exception('Chi nhánh không hợp lệ hoặc đang tạm dừng');
                }

                if (!$this->hasStock($branch->id, $validated['order_details'])) {
                    throw new \Exception('Chi nhánh đã hết hàng cho một hoặc nhiều sản phẩm');
                }
                $branchId = (int) $branch->id;
            } else { // home
                // Giao tận nơi → tự pick chi nhánh theo city + đủ tồn kho
                $city = (string) ($validated['shipping_city'] ?? '');
                $branchId = $this->pickBranchForDeliveryWithFallback($city, collect($validated['order_details']));


                if (!$branchId) {
                    throw new \Exception('Không có chi nhánh nào đủ hàng để giao');
                }
            }

            // --- Chuẩn bị dữ liệu order (giữ nguyên + bổ sung) ---
            $orderData = $validated;
            unset($orderData['order_details'], $orderData['voucher_code'], $orderData['shipping_city']);

            // Cập nhật tổng tiền sau giảm giá
            $orderData['total_amount'] = max($validated['total_amount'] - $discount, 0);
            // Luôn set status = 'pending' khi tạo đơn hàng
            $orderData['status'] = 'pending';
            // ✅ gán chi nhánh đã chọn và lưu delivery_type
            $orderData['branch_id'] = $branchId;
            $orderData['delivery_type'] = $validated['delivery_type'];

            $order = Order::create($orderData);

            foreach ($validated['order_details'] as $detail) {
                $order->orderDetails()->create([
                    'product_variant_id' => $detail['product_variant_id'],
                    'quantity'           => $detail['quantity'],
                ]);
            }

            if (!empty($validated['voucher_code']) && isset($userVoucher)) {
                $userVoucher->update(['is_used' => true]);
            }

            DB::commit();
            $order->load('orderDetails');

            return response()->json($order, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Tạo đơn hàng thất bại', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $order = Order::with([
            'orderItems.productVariant:id,product_id,name,img,price,discount',
            'orderItems.productVariant.product:id,name',
            'user:id,name,email'
        ])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        return response()->json($order);
    }


    // (Giữ nguyên) UPDATE đơn hàng + inventory
    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        $validated = $request->validate([
            'user_id'        => 'sometimes|integer|exists:users,id',
            'total_amount'   => 'sometimes|numeric|min:0',
            'payment_method' => 'sometimes|string|max:50',
            'status'         => 'sometimes|string|max:50',
            'note'           => 'nullable|string',
            'recipient_name' => 'sometimes|string|max:255',
            'phone'          => 'sometimes|string|max:20',
            'address'        => 'sometimes|string|max:255',
        ]);

        try {
            $oldStatus = $order->status;

            // Nếu yêu cầu huỷ
            if (isset($validated['status']) && $validated['status'] === 'cancelled') {
                if ($oldStatus !== 'pending') {
                    return response()->json([
                        'message' => '❌ Chỉ có thể huỷ đơn khi đang ở trạng thái chờ xác nhận.',
                        'current_status' => $oldStatus
                    ], 400);
                }
            }

            $order->update($validated);

            // Xử lý tồn kho
            if (isset($validated['status']) && $validated['status'] === 'cancelled' && in_array($oldStatus, ['confirmed', 'shipped', 'delivered'])) {
                \App\Services\InventoryService::increaseOrderInventory($order);
            } elseif ($oldStatus === 'pending' && $order->status === 'confirmed' && $order->payment_method === 'cod') {
                \App\Services\InventoryService::decreaseOrderInventory($order);
            }

            return response()->json([
                'message' => '✅ Cập nhật đơn hàng thành công',
                'order'   => $order
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Không thể cập nhật trạng thái',
                'errors' => $e->errors(),
                'current_status' => $order->status,
                'requested_status' => $validated['status'] ?? null
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }
        $order->delete();
        return response()->json(['message' => 'Đã xóa đơn hàng']);
    }

    public function sendInvoice($id)
    {
        $order = Order::with(['user', 'orderDetails.productVariant.product'])->findOrFail($id);

        if (!$order || $order->status !== 'confirmed') {
            return response()->json(['error' => 'Đơn hàng chưa được thanh toán'], 400);
        }

        try {
            Mail::to($order->user->email)->send(new InvoiceMail($order));
            return response()->json(['message' => 'Hóa đơn đã được gửi qua email!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Không thể gửi email: ' . $e->getMessage()], 500);
        }
    }

    // ======================================
    // Helpers: kiểm tra tồn kho & chọn CN giao
    // ======================================
    private function hasStock(int $branchId, array $items): bool
    {
        // NOTE: nếu bảng tồn kho của bạn dùng cột 'branch_id' thay vì 'warehouse_id',
        // đổi điều kiện tương ứng cho đúng schema.
        foreach ($items as $it) {
            $qty = DB::table('inventories')
                ->where('warehouse_id', $branchId)   // ⬅️ đổi thành ->where('branch_id', $branchId) nếu bạn đang dùng branch_id
                ->where('product_variant_id', $it['product_variant_id'])
                ->sum('quantity');

            if ($qty < (int) $it['quantity']) {
                return false;
            }
        }
        return true;
    }

    private function pickBranchForDelivery(string $city, \Illuminate\Support\Collection $items): ?int
    {
        if (!$city) return null;

        $branches = Branch::where('city', $city)->where('status', 1)->get();

        $candidates = $branches->filter(function ($b) use ($items) {
            return $this->hasStock($b->id, $items->toArray());
        });

        // Ưu tiên chi nhánh tồn kho tổng cao nhất (tuỳ bạn thay đổi quy tắc)
        $best = $candidates->sortByDesc(function ($b) {
            return DB::table('inventories')
                ->where('warehouse_id', $b->id)     // ⬅️ đổi thành 'branch_id' nếu schema bạn dùng
                ->sum('quantity');
        })->first();

        return $best?->id;
    }
    // --- Thêm mới: chuẩn hoá city để so sánh an toàn ---
    private function normalizeCity(?string $s): string
    {
        if (!$s) return '';
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s); // bỏ dấu
        $s = preg_replace('/[^A-Za-z0-9 ]/', ' ', $s);
        return trim(mb_strtolower(preg_replace('/\s+/', ' ', $s)));
    }

    // --- Thêm mới: chọn best branch trong 1 tập chi nhánh (đủ tất cả SKU, ưu tiên tổng tồn cao) ---
    private function chooseBestBranch($branches, array $items): ?int
    {
        if ($branches->isEmpty()) return null;

        // Lọc chi nhánh đủ hàng cho TẤT CẢ dòng trong đơn
        $candidates = $branches->filter(function ($b) use ($items) {
            return $this->hasStock($b->id, $items);
        });
        if ($candidates->isEmpty()) return null;

        // Ưu tiên tổng tồn kho toàn chi nhánh cao nhất
        $best = $candidates->sortByDesc(function ($b) {
            return DB::table('inventories')
                ->where('warehouse_id', $b->id)   // 🔁 ĐỔI thành branch_id nếu schema bạn dùng 'branch_id'
                ->sum('quantity');
        })->first();

        return $best?->id;
    }

    // --- Thêm mới: pick chi nhánh có Fallback TOÀN QUỐC ---
    // 1) thử cùng thành phố (sau khi normalize); 2) nếu không có → thử toàn bộ chi nhánh active
    private function pickBranchForDeliveryWithFallback(string $city, \Illuminate\Support\Collection $items): ?int
    {
        $normCity = $this->normalizeCity($city);
        $itemsArr = $items->toArray();

        // Tất cả chi nhánh đang hoạt động
        $allActive = Branch::where('status', 1)->get();

        // 1) Ứng viên CÙNG THÀNH PHỐ
        $sameCity = $allActive->filter(fn($b) => $this->normalizeCity($b->city) === $normCity);
        $bestInCity = $this->chooseBestBranch($sameCity, $itemsArr);
        if ($bestInCity) return $bestInCity;

        // 2) Fallback TOÀN QUỐC
        return $this->chooseBestBranch($allActive, $itemsArr);
    }
}
