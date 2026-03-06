<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        h2 {
            color: #0f172a;
            margin-top: 0;
        }

        .otp-box {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            letter-spacing: 5px;
            font-size: 32px;
            font-weight: bold;
            color: #3b82f6;
        }

        p {
            line-height: 1.6;
            color: #64748b;
        }

        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Xin chào!</h2>
        <p>Bạn vừa thực hiện yêu cầu xác thực tài khoản trên hệ thống <b>Tìm Thợ Sửa Chữa</b>.</p>
        <p>Đây là mã OTP bảo mật của bạn. Vui lòng không chia sẻ mã này cho bất kỳ ai:</p>

        <div class="otp-box">
            {{ $otp }}
        </div>

        <p>Mã OTP này sẽ hết hạn trong vòng <b>10 phút</b>.</p>
        <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>

        <div class="footer">
            Đây là email tự động, vui lòng không trả lời.<br>
            &copy; {{ date('Y') }} Nền tảng Tìm Thợ Sửa Chữa Chuyên Nghiệp.
        </div>
    </div>
</body>

</html>