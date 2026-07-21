<!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kiểm tra SMTP</title></head>
<body style="margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#1f2937">
<div style="max-width:600px;margin:32px auto;background:#fff;border-radius:12px;padding:32px;border:1px solid #e5e7eb">
    <div style="font-size:42px">✅</div>
    <h1 style="font-size:22px;margin:12px 0">Kết nối Email SMTP thành công</h1>
    <p style="line-height:1.6">{{ $shopName }} đã có thể gửi email xác nhận đơn hàng, cập nhật trạng thái và thông báo đơn mới.</p>
    <p style="color:#64748b;font-size:13px">Email kiểm tra được tạo lúc {{ now()->format('H:i d/m/Y') }}.</p>
</div>
</body>
</html>
