<!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đơn hàng mới</title></head>
<body style="margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#1f2937">
<div style="max-width:640px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb">
    <div style="background:#2563eb;color:#fff;padding:24px 30px">
        <h1 style="font-size:22px;margin:0">Có đơn hàng mới #{{ $order->order_number }}</h1>
    </div>
    <div style="padding:28px 30px;line-height:1.6">
        <p><strong>Khách hàng:</strong> {{ $order->customer_name }}</p>
        <p><strong>Điện thoại:</strong> {{ $order->customer_phone }}</p>
        <p><strong>Email:</strong> {{ $order->customer_email }}</p>
        <p><strong>Địa chỉ:</strong> {{ $order->shipping_address }}</p>
        <p><strong>Thanh toán:</strong> {{ strtoupper($order->payment_method) }} – {{ $order->payment_status }}</p>
        <p style="font-size:20px;color:#2563eb"><strong>Tổng cộng: {{ number_format((float) $order->grand_total, 0, ',', '.') }} ₫</strong></p>
    </div>
</div>
</body>
</html>
