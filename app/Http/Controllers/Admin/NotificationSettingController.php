<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureSetting;
use App\Models\ProjectSubscription;
use App\Services\NotificationService;
use App\Services\NotificationSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class NotificationSettingController extends Controller
{
    public function __construct(
        private readonly NotificationSettingsService $settingsService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index()
    {
        $settings = $this->settingsService->forForm();
        $configuredSecrets = $this->settingsService->configuredSecrets();
        $hasActiveSubscription = $this->hasActiveZaloSubscription();

        return view('admin.notification_settings.index', compact(
            'settings',
            'configuredSecrets',
            'hasActiveSubscription'
        ));
    }

    public function update(Request $request)
    {
        if (! $this->hasActiveZaloSubscription()) {
            $tryingToEnable = filter_var($request->input('zalo_oa.enabled'), FILTER_VALIDATE_BOOLEAN)
                || filter_var($request->input('zalo_personal.enabled'), FILTER_VALIDATE_BOOLEAN);

            if ($tryingToEnable) {
                return $this->errorResponse($request, __('admin.notification_settings.no_package'), 403);
            }
        }

        $validator = Validator::make($request->all(), $this->rules());
        $validator->after(function ($validator) use ($request): void {
            $requiredSecrets = [
                'zalo_oa' => [
                    'secret_key' => 'zalo_oa.secret_key',
                    'access_token' => 'zalo_oa.access_token',
                    'refresh_token' => 'zalo_oa.refresh_token',
                ],
                'zalo_personal' => [
                    'bot_token' => 'zalo_personal.bot_token',
                ],
                'smtp' => [
                    'password' => 'smtp.password',
                ],
            ];

            foreach ($requiredSecrets as $section => $fields) {
                if (! filter_var($request->input("{$section}.enabled"), FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                foreach ($fields as $field => $path) {
                    if (trim((string) $request->input("{$section}.{$field}")) === ''
                        && ! $this->settingsService->hasConfiguredSecret($path)) {
                        $validator->errors()->add("{$section}.{$field}", __('validation.required', [
                            'attribute' => str_replace('_', ' ', $field),
                        ]));
                    }
                }
            }
        });

        $validated = $validator->validate();
        $settingsData = $this->normalizeSettings($validated);
        $this->settingsService->save($settingsData);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('admin.notification_settings.save_success'),
                'configured_secrets' => $this->settingsService->configuredSecrets(),
            ]);
        }

        return redirect()
            ->route('admin.notification-settings.index')
            ->with('success', __('admin.notification_settings.save_success'));
    }

    public function testSmtp(Request $request)
    {
        $validated = $request->validate([
            'smtp.host' => 'required|string|max:255',
            'smtp.port' => 'required|integer|min:1|max:65535',
            'smtp.encryption' => 'required|in:ssl,tls,none',
            'smtp.username' => 'required|string|max:255',
            'smtp.password' => 'nullable|string|max:255',
            'smtp.from_email' => 'required|email|max:255',
            'smtp.from_name' => 'required|string|max:255',
            'smtp.owner_email' => 'nullable|email|max:255',
            'test_email' => 'nullable|email|max:255',
        ]);

        $settings = $this->settingsService->mergeForTest([
            'smtp' => $validated['smtp'],
        ]);
        $recipient = (string) (($validated['test_email'] ?? null)
            ?: data_get($settings, 'smtp.owner_email')
            ?: data_get($settings, 'smtp.from_email'));

        if ($recipient === '') {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập email nhận thông báo hoặc email gửi thử.',
            ], 422);
        }

        try {
            $this->notificationService->sendTestEmail((array) data_get($settings, 'smtp'), $recipient);

            return response()->json([
                'success' => true,
                'message' => "Đã gửi email kiểm tra đến {$recipient}.",
            ]);
        } catch (Throwable $exception) {
            Log::warning('SMTP connection test failed.', ['error' => $exception->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email. Hãy kiểm tra Host, Port, mã hóa, tài khoản và App Password.',
            ], 422);
        }
    }

    public function testZaloPersonal(Request $request)
    {
        $validated = $request->validate([
            'zalo_personal.bot_token' => 'nullable|string|max:1000',
            'zalo_personal.chat_id' => 'required|string|max:255',
        ]);

        $settings = $this->settingsService->mergeForTest([
            'zalo_personal' => $validated['zalo_personal'],
        ]);

        try {
            $this->notificationService->sendTestZalo((array) data_get($settings, 'zalo_personal'));

            return response()->json([
                'success' => true,
                'message' => 'Đã gửi thông báo thử đến Zalo thành công.',
            ]);
        } catch (Throwable $exception) {
            Log::warning('Zalo Bot connection test failed.', ['error' => $exception->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi Zalo. Hãy kiểm tra Bot Token, Chat ID và thử lại.',
            ], 422);
        }
    }

    public function testZaloOa(Request $request)
    {
        if (! $this->hasActiveZaloSubscription()) {
            return response()->json([
                'success' => false,
                'message' => __('admin.notification_settings.no_package'),
            ], 403);
        }

        $validated = $request->validate([
            'zalo_oa.app_id' => 'required|string|max:255',
            'zalo_oa.secret_key' => 'nullable|string|max:255',
            'zalo_oa.access_token' => 'nullable|string|max:1000',
            'zalo_oa.refresh_token' => 'nullable|string|max:1000',
            'zalo_oa.template_id' => 'required|string|max:255',
            'zalo_oa.template_data' => 'required|json|max:5000',
            'zalo_oa_test_phone' => ['required', 'string', 'regex:/^(\+?84|0)[0-9]{9,10}$/'],
        ]);

        $settings = $this->settingsService->mergeForTest([
            'zalo_oa' => $validated['zalo_oa'],
        ]);

        try {
            $this->notificationService->sendTestZaloOa(
                (array) data_get($settings, 'zalo_oa'),
                $validated['zalo_oa_test_phone']
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã gửi Zalo OA/ZNS thử thành công.',
            ]);
        } catch (Throwable $exception) {
            Log::warning('Zalo OA connection test failed.', ['error' => $exception->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi Zalo OA. Hãy kiểm tra Access Token, Template ID, số điện thoại và dữ liệu mẫu đã được Zalo duyệt.',
            ], 422);
        }
    }

    public function getZaloChatId(Request $request)
    {
        $validated = $request->validate([
            'bot_token' => 'nullable|string|max:1000',
        ]);

        $settings = $this->settingsService->mergeForTest([
            'zalo_personal' => ['bot_token' => $validated['bot_token'] ?? ''],
        ]);
        $botToken = preg_replace(
            '/^zbot:/i',
            '',
            trim((string) data_get($settings, 'zalo_personal.bot_token', ''))
        );

        if ($botToken === '') {
            return response()->json([
                'message' => __('validation.required', ['attribute' => 'bot token']),
                'errors' => ['bot_token' => [__('validation.required', ['attribute' => 'bot token'])]],
            ], 422);
        }

        try {
            $response = Http::timeout(15)->post(
                "https://bot-api.zaloplatforms.com/bot{$botToken}/getUpdates",
                ['timeout' => 5]
            );

            if (! $response->successful() || ! $response->json('ok')) {
                return response()->json([
                    'success' => false,
                    'message' => __('admin.notification_settings.zalo_personal.get_chat_id_error'),
                ], 400);
            }

            $chats = [];
            foreach ((array) ($response->json('result') ?? []) as $update) {
                $chatId = data_get($update, 'message.chat.id');
                $displayName = data_get($update, 'message.from.display_name')
                    ?? data_get($update, 'message.from.username')
                    ?? 'Zalo User';

                if ($chatId) {
                    $chats[$chatId] = [
                        'chat_id' => $chatId,
                        'display_name' => $displayName,
                    ];
                }
            }

            $chats = array_values($chats);

            return response()->json([
                'success' => true,
                'chats' => $chats,
                'message' => empty($chats)
                    ? __('admin.notification_settings.zalo_personal.get_chat_id_empty')
                    : null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Unable to retrieve Zalo Chat ID.', ['error' => $exception->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => __('admin.notification_settings.zalo_personal.get_chat_id_error'),
            ], 500);
        }
    }

    private function rules(): array
    {
        return [
            'zalo_oa.enabled' => 'nullable|boolean',
            'zalo_oa.app_id' => 'required_if:zalo_oa.enabled,1,true|nullable|string|max:255',
            'zalo_oa.secret_key' => 'nullable|string|max:255',
            'zalo_oa.access_token' => 'nullable|string|max:1000',
            'zalo_oa.refresh_token' => 'nullable|string|max:1000',
            'zalo_oa.template_id' => 'required_if:zalo_oa.enabled,1,true|nullable|string|max:255',
            'zalo_oa.template_data' => 'nullable|json|max:5000',

            'zalo_personal.enabled' => 'nullable|boolean',
            'zalo_personal.bot_token' => 'nullable|string|max:1000',
            'zalo_personal.chat_id' => 'required_if:zalo_personal.enabled,1,true|nullable|string|max:255',

            'smtp.enabled' => 'nullable|boolean',
            'smtp.host' => 'required_if:smtp.enabled,1,true|nullable|string|max:255',
            'smtp.port' => 'required_if:smtp.enabled,1,true|nullable|integer|min:1|max:65535',
            'smtp.encryption' => 'required_if:smtp.enabled,1,true|nullable|in:ssl,tls,none',
            'smtp.username' => 'required_if:smtp.enabled,1,true|nullable|string|max:255',
            'smtp.password' => 'nullable|string|max:255',
            'smtp.from_email' => 'required_if:smtp.enabled,1,true|nullable|email|max:255',
            'smtp.from_name' => 'required_if:smtp.enabled,1,true|nullable|string|max:255',
            'smtp.owner_email' => 'required_if:smtp.enabled,1,true|nullable|email|max:255',

            'dashboard.enabled' => 'nullable|boolean',
            'dashboard.play_sound' => 'nullable|boolean',
            'dashboard.auto_refresh' => 'nullable|boolean',
        ];
    }

    private function normalizeSettings(array $validated): array
    {
        $defaults = $this->settingsService->defaults();
        $settings = array_replace_recursive($defaults, $this->settingsService->get(), $validated);

        foreach (['zalo_oa', 'zalo_personal', 'smtp', 'dashboard'] as $section) {
            $settings[$section]['enabled'] = (bool) data_get($validated, "{$section}.enabled", false);
        }

        $settings['dashboard']['play_sound'] = (bool) data_get($validated, 'dashboard.play_sound', false);
        $settings['dashboard']['auto_refresh'] = (bool) data_get($validated, 'dashboard.auto_refresh', false);
        $settings['smtp']['port'] = isset($settings['smtp']['port']) ? (int) $settings['smtp']['port'] : 587;

        return $settings;
    }

    private function hasActiveZaloSubscription(): bool
    {
        $subscription = ProjectSubscription::query()
            ->where('status', 'active')
            ->whereNotNull('package_id')
            ->where(function ($query): void {
                $query->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now()->toDateString());
            })
            ->exists();

        return $subscription && FeatureSetting::query()
            ->where('feature_code', 'zalo_oa')
            ->where('is_enabled', true)
            ->exists();
    }

    private function errorResponse(Request $request, string $message, int $status)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
