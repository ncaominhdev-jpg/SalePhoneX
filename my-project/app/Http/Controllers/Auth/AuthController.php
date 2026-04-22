<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class AuthController extends Controller
{
    // Đăng ký user mới
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = Hash::make($request->password);
        $user->status = 1;
        $user->role = 'user';
        $user->remember_token = Str::random(60);
        $user->save();

        return response()->json([
            'message' => 'Đăng ký thành công',
            'access_token' => $user->remember_token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    // Đăng nhập
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không đúng.',
            ], 401);
        }

        if ($user->status != 1) {
            return response()->json([
                'message' => 'Tài khoản đã bị vô hiệu hóa.',
            ], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không đúng.',
            ], 401);
        }

        // Tạo token mới
        $user->remember_token = Str::random(60);
        $user->save();

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'access_token' => $user->remember_token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    // Đăng xuất
    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'message' => 'Token không hợp lệ.',
                ], 401);
            }

            $user = User::where('remember_token', $token)->first();

            if ($user) {
                $user->remember_token = null;
                $user->save();
            }

            return response()->json([
                'message' => 'Đăng xuất thành công',
            ]);
        } catch (Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Lỗi khi đăng xuất',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Quên mật khẩu - gửi token lấy lại mật khẩu
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Không tìm thấy email trong hệ thống.',
                ], 404);
            }

            // Tạo token reset password
            $token = Str::random(60);

            // Lưu token vào bảng password_reset_tokens
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'email' => $request->email,
                    'token' => $token,
                    'created_at' => Carbon::now(),
                ]
            );

            // Gửi email chứa link reset mật khẩu
            $resetLink = url('/reset-password?token=' . $token . '&email=' . urlencode($request->email));

            Mail::send([], [], function ($message) use ($request, $resetLink) {
                $message->to($request->email)
                    ->subject('Yêu cầu đặt lại mật khẩu')
                    ->html('Bạn nhận được email này vì đã yêu cầu đặt lại mật khẩu. Vui lòng nhấp vào liên kết sau để đặt lại mật khẩu: ' . $resetLink);
            });

            return response()->json([
                'message' => 'Đã gửi email lấy lại mật khẩu. Vui lòng kiểm tra hộp thư đến.',
            ]);
        } catch (Exception $e) {
            Log::error('Forgot password error: ' . $e->getMessage());
            if (config('app.debug')) {
                return response()->json([
                    'message' => 'Lỗi khi gửi email lấy lại mật khẩu.',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], 500);
            } else {
                return response()->json([
                    'message' => 'Lỗi khi gửi email lấy lại mật khẩu.',
                ], 500);
            }
        }
    }

    // Lấy lại mật khẩu - reset mật khẩu bằng token
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'message' => 'Token hoặc email không hợp lệ.',
            ], 400);
        }

        // Kiểm tra token có hết hạn không (ví dụ 60 phút)
        if (Carbon::parse($passwordReset->created_at)->addMinutes(60)->isPast()) {
            return response()->json([
                'message' => 'Token đã hết hạn.',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Không tìm thấy người dùng.',
            ], 404);
        }

        // Cập nhật mật khẩu mới
        $user->password = Hash::make($request->password);
        $user->save();

        // Xóa token reset password
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Đặt lại mật khẩu thành công.',
        ]);
    }

    // Đổi mật khẩu khi đã đăng nhập
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Token không hợp lệ.',
            ], 401);
        }

        $user = User::where('remember_token', $token)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Người dùng không tồn tại.',
            ], 404);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Mật khẩu hiện tại không đúng.',
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }
}
