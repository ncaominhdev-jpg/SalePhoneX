<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    // Lấy tất cả voucher đang hoạt động
    public function index()
    {
        $vouchers = Voucher::where('status', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->get();

        return response()->json($vouchers);
    }

    // Admin tạo voucher
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:vouchers,code',
            'description' => 'nullable|string',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:1',
            'min_order_value' => 'required|numeric|min:0',
            'usage_limit' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|boolean',
        ]);

        $validated['used'] = 0; // Khởi tạo số lượt dùng
        $voucher = Voucher::create($validated);

        return response()->json(['message' => 'Tạo voucher thành công', 'voucher' => $voucher]);
    }

    // Áp dụng voucher khi user nhập
    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
        ]);

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json(['message' => 'Voucher không tồn tại'], 404);
        }

        if (!$voucher->isValid($request->total_amount)) {
            return response()->json(['message' => 'Voucher không hợp lệ hoặc đã hết hạn/số lượt'], 400);
        }

        // Tính giảm giá
        $discount = $voucher->type === 'percent'
            ? $request->total_amount * ($voucher->value / 100)
            : $voucher->value;

        $finalTotal = max($request->total_amount - $discount, 0);

        // Cập nhật số lượt dùng
        $voucher->increment('used');

        return response()->json([
            'message' => 'Voucher hợp lệ',
            'discount' => $discount,
            'final_total' => $finalTotal
        ]);
    }


    public function getAvailableVouchers(Request $request)
    {
        $userId = $request->user()->id ?? null;

        $vouchers = \App\Models\Voucher::where('status', 1)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->where('usage_limit', '>', DB::raw('used'))
            ->get(['id', 'code', 'description', 'type', 'value', 'min_order_value']);

        return response()->json($vouchers);
    }
    public function claimVoucher(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'voucher_id' => 'required|exists:vouchers,id',
        ]);

        // Kiểm tra đã nhận chưa
        $exists = \App\Models\UserVoucher::where('user_id', $request->user_id)
            ->where('voucher_id', $request->voucher_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Bạn đã nhận voucher này rồi'], 400);
        }

        $userVoucher = \App\Models\UserVoucher::create([
            'user_id' => $request->user_id,
            'voucher_id' => $request->voucher_id,
        ]);

        return response()->json(['message' => 'Nhận voucher thành công', 'data' => $userVoucher]);
    }
    public function myVouchers(Request $request)
    {
        $userId = $request->query('user_id');

        $vouchers = \App\Models\UserVoucher::with('voucher')
            ->where('user_id', $userId)
            ->where('is_used', false)
            ->get()
            ->pluck('voucher');

        return response()->json($vouchers);
    }
    public function assignToUser($userId, $voucherId)
    {
        $voucher = Voucher::findOrFail($voucherId);

        $exists = \App\Models\UserVoucher::where('user_id', $userId)
            ->where('voucher_id', $voucherId)
            ->first();

        if ($exists) {
            return response()->json(['message' => 'Bạn đã nhận voucher này rồi'], 400);
        }

        \App\Models\UserVoucher::create([
            'user_id' => $userId,
            'voucher_id' => $voucherId,
            'is_used' => false,
        ]);

        return response()->json(['message' => 'Nhận voucher thành công']);
    }
    public function userVouchers($id)
    {
        $vouchers = DB::table('user_vouchers')
            ->join('vouchers', 'user_vouchers.voucher_id', '=', 'vouchers.id')
            ->where('user_vouchers.user_id', $id)
            ->select('vouchers.*', 'user_vouchers.is_used')
            ->get();

        return response()->json($vouchers);
    }
   
}
