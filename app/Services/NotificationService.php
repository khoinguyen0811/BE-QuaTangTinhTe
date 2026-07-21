<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Mail\NewOrderAdminMail;
use App\Mail\OrderStatusMail;
use App\Mail\TestNotificationMail;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class NotificationService
{
    public function __construct(private readonly NotificationSettingsService $settingsService) {}

    /**
     * Fan a newly-created order out to every configured channel from one entry point.
     */
    public function notifyOrderCreated(Order $order): array
    {
        $customerResults = $this->notifyCustomerOrderStatus($order);
        $adminResults = $this->notifyNewOrder($order);

        return array_merge($customerResults, $adminResults, [
            // Dashboard notifications are derived from the orders table and discovered by polling.
            'dashboard' => (bool) data_get($this->settingsService->get(false), 'dashboard.enabled', true),
        ]);
    }

    public function sendCustomerOrderStatus(Order $order): bool
    {
        return $this->notifyCustomerOrderStatus($order)['customer_email'];
    }

    private function notifyCustomerOrderStatus(Order $order): array
    {
        $settings = $this->settingsService->get();
        $emailSent = false;

        if (! $this->settingsService->exists() || data_get($settings, 'smtp.enabled')) {
            $emailSent = $this->sendMailSafely(
                $order->customer_email,
                new OrderStatusMail($order),
                "order status email for order {$order->id}"
            );
        }

        $zaloOaSent = $this->sendZaloOaOrderStatusSafely($order);

        return [
            'customer_email' => $emailSent,
            'zalo_oa' => $zaloOaSent,
        ];
    }

    public function sendInvoice(Invoice $invoice, string $recipient, bool $throw = false): bool
    {
        if ($throw) {
            $this->sendMail($recipient, new InvoiceMail($invoice));

            return true;
        }

        return $this->sendMailSafely(
            $recipient,
            new InvoiceMail($invoice),
            "invoice {$invoice->id}"
        );
    }

    /**
     * Admin alerts are isolated by channel so one provider can never block another.
     */
    public function notifyNewOrder(Order $order): array
    {
        $results = [
            'smtp' => false,
            'zalo_personal' => false,
        ];

        $settings = $this->settingsService->get();

        if (data_get($settings, 'smtp.enabled') && data_get($settings, 'smtp.owner_email')) {
            $results['smtp'] = $this->sendMailSafely(
                (string) data_get($settings, 'smtp.owner_email'),
                new NewOrderAdminMail($order),
                "owner new-order email for order {$order->id}"
            );
        }

        if (data_get($settings, 'zalo_personal.enabled')) {
            try {
                $this->sendZaloMessage(
                    (array) data_get($settings, 'zalo_personal', []),
                    $this->newOrderMessage($order)
                );
                $results['zalo_personal'] = true;
            } catch (Throwable $exception) {
                Log::warning('Zalo Bot new-order notification failed.', [
                    'order_id' => $order->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $results;
    }

    public function sendTestEmail(array $smtp, string $recipient): void
    {
        $this->validateSmtp($smtp);
        $mailer = $this->settingsService->configureSmtp($smtp);

        Mail::mailer($mailer)
            ->to($recipient)
            ->send(new TestNotificationMail((string) ($smtp['from_name'] ?? config('app.name'))));
    }

    public function sendTestZalo(array $zaloPersonal): void
    {
        $this->sendZaloMessage(
            $zaloPersonal,
            "✅ Kết nối Zalo Bot thành công.\nWebsite đã sẵn sàng gửi thông báo đơn hàng mới."
        );
    }

    public function sendTestZaloOa(array $zaloOa, string $phone): void
    {
        $this->sendZaloOaTemplate($zaloOa, $phone, [
            'order_number' => 'TEST-'.now()->format('His'),
            'customer_name' => 'Khách hàng kiểm tra',
            'status' => 'pending',
            'grand_total' => '100.000',
        ]);
    }

    private function sendMailSafely(?string $recipient, object $mailable, string $context): bool
    {
        if (! $recipient) {
            return false;
        }

        try {
            $this->sendMail($recipient, $mailable);

            return true;
        } catch (Throwable $exception) {
            Log::warning("Failed to send {$context}.", [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendMail(string $recipient, object $mailable): void
    {
        $settings = $this->settingsService->get();

        if ($this->settingsService->exists()) {
            if (! data_get($settings, 'smtp.enabled')) {
                throw new RuntimeException('Kênh Email SMTP đang tắt. Hãy bật và lưu cấu hình trong phần Thông báo.');
            }

            $smtp = (array) data_get($settings, 'smtp', []);
            $this->validateSmtp($smtp);
            $mailer = $this->settingsService->configureSmtp($smtp);
            Mail::mailer($mailer)->to($recipient)->send($mailable);

            return;
        }

        // Backward compatibility for installations that have not opened the new settings page yet.
        Mail::to($recipient)->send($mailable);
    }

    private function sendZaloOaOrderStatusSafely(Order $order): bool
    {
        if (! $this->settingsService->exists()) {
            return false;
        }

        $zaloOa = (array) data_get($this->settingsService->get(), 'zalo_oa', []);
        if (! data_get($zaloOa, 'enabled')) {
            return false;
        }

        try {
            $this->sendZaloOaTemplate($zaloOa, (string) $order->customer_phone, [
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'status' => $order->status,
                'grand_total' => number_format((float) $order->grand_total, 0, ',', '.'),
            ], 'order_'.$order->id.'_'.$order->status);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Zalo OA order notification failed.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendZaloOaTemplate(
        array $settings,
        string $phone,
        array $context,
        ?string $trackingId = null
    ): void {
        foreach (['access_token', 'template_id', 'template_data'] as $field) {
            if (trim((string) ($settings[$field] ?? '')) === '') {
                throw new RuntimeException("Thiếu cấu hình Zalo OA: {$field}.");
            }
        }

        $payload = [
            'phone' => $this->normalizeVietnamPhone($phone),
            'template_id' => (string) $settings['template_id'],
            'template_data' => $this->renderZaloTemplateData((string) $settings['template_data'], $context),
            'tracking_id' => $trackingId ?? 'test_'.now()->format('YmdHis'),
        ];

        $response = $this->postZaloOaTemplate((string) $settings['access_token'], $payload);
        $errorCode = (int) ($response->json('error') ?? 0);

        if (($response->status() === 401 || in_array($errorCode, [-201, -216, -124], true))
            && $this->canRefreshZaloOaToken($settings)) {
            $settings = $this->refreshZaloOaToken($settings);
            $response = $this->postZaloOaTemplate((string) $settings['access_token'], $payload);
            $errorCode = (int) ($response->json('error') ?? 0);
        }

        if (! $response->successful() || $errorCode !== 0) {
            throw new RuntimeException('Zalo OA từ chối yêu cầu gửi template. Mã lỗi: '.$errorCode);
        }
    }

    private function postZaloOaTemplate(string $accessToken, array $payload)
    {
        return Http::timeout(15)
            ->withHeaders(['access_token' => $accessToken])
            ->post('https://business.openapi.zalo.me/message/template', $payload);
    }

    private function canRefreshZaloOaToken(array $settings): bool
    {
        return trim((string) ($settings['app_id'] ?? '')) !== ''
            && trim((string) ($settings['secret_key'] ?? '')) !== ''
            && trim((string) ($settings['refresh_token'] ?? '')) !== '';
    }

    private function refreshZaloOaToken(array $settings): array
    {
        $response = Http::asForm()
            ->timeout(15)
            ->withHeaders(['secret_key' => (string) $settings['secret_key']])
            ->post('https://oauth.zaloapp.com/v4/oa/access_token', [
                'app_id' => (string) $settings['app_id'],
                'grant_type' => 'refresh_token',
                'refresh_token' => (string) $settings['refresh_token'],
            ]);

        if (! $response->successful() || ! $response->json('access_token')) {
            throw new RuntimeException('Không thể tự động làm mới Zalo OA Access Token.');
        }

        $settings['access_token'] = (string) $response->json('access_token');
        if ($response->json('refresh_token')) {
            $settings['refresh_token'] = (string) $response->json('refresh_token');
        }

        $this->settingsService->save(['zalo_oa' => $settings]);

        return $settings;
    }

    private function renderZaloTemplateData(string $json, array $context): array
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data)) {
            throw new RuntimeException('Dữ liệu mẫu Zalo OA phải là JSON object.');
        }

        array_walk_recursive($data, function (&$value) use ($context): void {
            if (! is_string($value)) {
                return;
            }

            foreach ($context as $key => $replacement) {
                $value = str_replace('{{'.$key.'}}', (string) $replacement, $value);
            }
        });

        return $data;
    }

    private function normalizeVietnamPhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($phone, '0')) {
            $phone = '84'.substr($phone, 1);
        }

        if (! preg_match('/^84[0-9]{9,10}$/', $phone)) {
            throw new RuntimeException('Số điện thoại nhận Zalo không hợp lệ.');
        }

        return $phone;
    }

    private function sendZaloMessage(array $settings, string $text): void
    {
        $botToken = preg_replace('/^zbot:/i', '', trim((string) ($settings['bot_token'] ?? '')));
        $chatId = trim((string) ($settings['chat_id'] ?? ''));

        if ($botToken === '' || $chatId === '') {
            throw new RuntimeException('Bot Token hoặc Chat ID chưa được cấu hình.');
        }

        $response = Http::timeout(15)->post(
            "https://bot-api.zaloplatforms.com/bot{$botToken}/sendMessage",
            [
                'chat_id' => $chatId,
                'text' => $text,
            ]
        );

        if (! $response->successful() || $response->json('ok') === false) {
            throw new RuntimeException('Zalo Bot từ chối yêu cầu gửi tin nhắn.');
        }
    }

    private function validateSmtp(array $smtp): void
    {
        foreach (['host', 'port', 'username', 'password', 'from_email', 'from_name'] as $field) {
            if (trim((string) ($smtp[$field] ?? '')) === '') {
                throw new RuntimeException("Thiếu cấu hình SMTP: {$field}.");
            }
        }
    }

    private function newOrderMessage(Order $order): string
    {
        return implode("\n", [
            '🔔 ĐƠN HÀNG MỚI',
            "Mã đơn: #{$order->order_number}",
            "Khách hàng: {$order->customer_name}",
            "Số điện thoại: {$order->customer_phone}",
            'Tổng cộng: '.number_format((float) $order->grand_total, 0, ',', '.').' ₫',
            "Địa chỉ: {$order->shipping_address}",
        ]);
    }
}
