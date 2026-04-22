<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reset Mật Khẩu</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f9fafb;
            margin: 0;
            padding: 0;
        }

        .email-wrapper {
            width: 100%;
            background: #f9fafb;
            padding: 40px 0;
        }

        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .email-header {
            background: linear-gradient(135deg, #dc2626, #f87171);
            color: white;
            text-align: center;
            padding: 25px 20px;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 1px;
        }

        .email-body {
            padding: 30px 25px;
            color: #444;
        }

        .email-body p {
            margin-bottom: 15px;
            font-size: 15px;
        }

        .button {
            display: inline-block;
            padding: 14px 28px;
            background-color: #dc2626;
            color: #ffffff;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
            margin: 20px 0;
            transition: background 0.3s ease;
        }

        .button:hover {
            background-color: #b91c1c;
        }

        .link-box {
            word-break: break-all;
            background: #f3f4f6;
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
            color: #111827;
            margin-top: 10px;
        }

        .email-footer {
            background: #f9fafb;
            padding: 20px;
            font-size: 13px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="email-header">
                <h1>Đặt lại mật khẩu</h1>
            </div>
            <div class="email-body">
                <p>Xin chào,</p>
                <p>Chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>

                <div style="text-align: center;">
                    <a href="{{ $resetUrl }}"
                        style="display:inline-block;
              padding:14px 28px;
              background-color:#dc2626;
              color:#ffffff !important;
              font-weight:bold;
              text-decoration:none;
              border-radius:8px;
              margin:20px 0;
              font-family:Arial,Helvetica,sans-serif;
              font-size:16px;">
                        Đặt lại mật khẩu
                    </a>
                </div>


                <p>Nếu nút trên không hoạt động, hãy sao chép liên kết bên dưới và dán vào trình duyệt:</p>
                <div class="link-box">
                    {{ $resetUrl }}
                </div>

                <p><strong>Lưu ý:</strong> Liên kết này sẽ hết hạn sau 60 phút.</p>
                <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
            </div>
            <div class="email-footer">
                <p>Trân trọng,<br />Đội ngũ {{ config('app.name') }}</p>
            </div>
        </div>
    </div>
</body>

</html>
