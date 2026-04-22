@component('mail::message')
# Xin chào {{ $user->name ?? 'bạn' }}, 👋

Chúng tôi nhận được yêu cầu **đặt lại mật khẩu** cho tài khoản của bạn.

@component('mail::button', ['url' => $url])
🔐 Đặt lại mật khẩu
@endcomponent

Liên kết trên sẽ hết hạn sau 60 phút. Nếu bạn không yêu cầu đặt lại mật khẩu, bạn có thể bỏ qua email này.

Cảm ơn bạn đã sử dụng hệ thống của chúng tôi!  
**{{ config('app.name') }}**

---

Nếu nút không bấm được, bạn có thể truy cập trực tiếp đường dẫn sau:

[{{ $url }}]({{ $url }})
@endcomponent
