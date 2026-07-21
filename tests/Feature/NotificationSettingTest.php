<?php

namespace Tests\Feature;

use App\Mail\NewOrderAdminMail;
use App\Mail\OrderStatusMail;
use App\Mail\TestNotificationMail;
use App\Models\Addon;
use App\Models\FeatureSetting;
use App\Models\Order;
use App\Models\Package;
use App\Models\ProjectSetting;
use App\Models\ProjectSubscription;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\NotificationSettingsService;
use App\Support\NotificationHelper;
use Database\Seeders\AddonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::query()->create([
            'name' => 'Admin',
            'permissions' => ['manage_settings'],
        ]);

        $guestRole = Role::query()->create([
            'name' => 'Guest',
            'permissions' => [],
        ]);

        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
        ]);

        $this->guestUser = User::factory()->create([
            'role_id' => $guestRole->id,
        ]);

        (new AddonSeeder)->run();
        Addon::query()->update(['is_purchased' => true]);

        $package = Package::query()->create([
            'code' => 'basic_2m',
            'name' => 'Basic 2M',
            'price' => 2000000.00,
            'is_active' => true,
        ]);

        ProjectSubscription::query()->create([
            'package_id' => $package->id,
            'status' => 'active',
            'started_at' => now(),
            'expired_at' => null,
        ]);

        FeatureSetting::query()->create([
            'feature_code' => 'zalo_oa',
            'is_enabled' => true,
        ]);
    }

    public function test_guests_cannot_access_notification_settings(): void
    {
        $response = $this->get('/vi/admin/notification-settings');
        $response->assertRedirect('/login');
    }

    public function test_unauthorized_users_cannot_access_notification_settings(): void
    {
        $response = $this->actingAs($this->guestUser)->get('/vi/admin/notification-settings');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_notification_settings_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/vi/admin/notification-settings');

        $response->assertOk();
        $response->assertViewIs('admin.notification_settings.index');
    }

    public function test_admin_can_update_notification_settings(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/vi/admin/notification-settings', [
            'zalo_oa' => [
                'enabled' => '1',
                'app_id' => '12345678',
                'secret_key' => 'secret_val',
                'access_token' => 'access_val',
                'refresh_token' => 'refresh_val',
                'template_id' => '99999',
            ],
            'zalo_personal' => [
                'enabled' => '1',
                'bot_token' => 'bot_token_val',
                'chat_id' => 'chat_id_val',
            ],
            'smtp' => [
                'enabled' => '1',
                'host' => 'smtp.test.com',
                'port' => '587',
                'encryption' => 'tls',
                'username' => 'testuser',
                'password' => 'testpass',
                'from_email' => 'shop@test.com',
                'from_name' => 'Shop Name',
                'owner_email' => 'boss@test.com',
            ],
        ]);

        $response->assertRedirect('/vi/admin/notification-settings');
        $response->assertSessionHas('success');

        $setting = ProjectSetting::where('setting_key', 'notification_settings')->first();
        $this->assertNotNull($setting);

        $value = $setting->setting_value;
        $this->assertTrue($value['zalo_oa']['enabled']);
        $this->assertEquals('12345678', $value['zalo_oa']['app_id']);
        $this->assertStringStartsWith('encrypted:', $value['zalo_oa']['secret_key']);
        $this->assertStringStartsWith('encrypted:', $value['zalo_oa']['access_token']);
        $this->assertStringStartsWith('encrypted:', $value['zalo_oa']['refresh_token']);
        $this->assertEquals('99999', $value['zalo_oa']['template_id']);
        $this->assertTrue($value['zalo_personal']['enabled']);
        $this->assertStringStartsWith('encrypted:', $value['zalo_personal']['bot_token']);
        $this->assertEquals('chat_id_val', $value['zalo_personal']['chat_id']);
        $this->assertTrue($value['smtp']['enabled']);
        $this->assertEquals('smtp.test.com', $value['smtp']['host']);
        $this->assertEquals(587, $value['smtp']['port']);
        $this->assertEquals('tls', $value['smtp']['encryption']);
        $this->assertEquals('testuser', $value['smtp']['username']);
        $this->assertStringStartsWith('encrypted:', $value['smtp']['password']);
        $this->assertEquals('shop@test.com', $value['smtp']['from_email']);
        $this->assertEquals('Shop Name', $value['smtp']['from_name']);
        $this->assertEquals('boss@test.com', $value['smtp']['owner_email']);

        $decrypted = app(NotificationSettingsService::class)->get();
        $this->assertEquals('secret_val', $decrypted['zalo_oa']['secret_key']);
        $this->assertEquals('access_val', $decrypted['zalo_oa']['access_token']);
        $this->assertEquals('refresh_val', $decrypted['zalo_oa']['refresh_token']);
        $this->assertEquals('bot_token_val', $decrypted['zalo_personal']['bot_token']);
        $this->assertEquals('testpass', $decrypted['smtp']['password']);
    }

    public function test_validation_fails_when_enabled_channel_has_missing_fields(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/vi/admin/notification-settings', [
            'zalo_oa' => [
                'enabled' => '1',
                'app_id' => '', // missing app_id
                'template_id' => '99999',
            ],
            'zalo_personal' => [
                'enabled' => '1',
                'bot_token' => '', // missing bot_token
                'chat_id' => 'chat_id_val',
            ],
        ]);

        $response->assertStatus(302); // Redirects back with validation errors
        $response->assertSessionHasErrors([
            'zalo_oa.app_id',
            'zalo_oa.secret_key',
            'zalo_oa.access_token',
            'zalo_oa.refresh_token',
            'zalo_personal.bot_token',
        ]);
    }

    public function test_get_zalo_chat_id_requires_authentication(): void
    {
        $response = $this->postJson('/vi/admin/notification-settings/get-zalo-chat-id', [
            'bot_token' => 'dummy_token',
        ]);
        $response->assertStatus(401); // Unauthorized
    }

    public function test_get_zalo_chat_id_requires_bot_token(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson('/vi/admin/notification-settings/get-zalo-chat-id', [
            'bot_token' => '',
        ]);
        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors(['bot_token']);
    }

    public function test_get_zalo_chat_id_success(): void
    {
        Http::fake([
            'https://bot-api.zaloplatforms.com/*' => Http::response([
                'ok' => true,
                'result' => [
                    [
                        'update_id' => 123,
                        'message' => [
                            'message_id' => 456,
                            'from' => [
                                'id' => 'zalo_user_1',
                                'display_name' => 'Nguyễn Văn A',
                            ],
                            'chat' => [
                                'id' => 'chat_1',
                            ],
                            'text' => 'hello',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser)->postJson('/vi/admin/notification-settings/get-zalo-chat-id', [
            'bot_token' => 'valid_bot_token',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'chats' => [
                [
                    'chat_id' => 'chat_1',
                    'display_name' => 'Nguyễn Văn A',
                ],
            ],
        ]);
    }

    public function test_get_zalo_chat_id_failure(): void
    {
        Http::fake([
            'https://bot-api.zaloplatforms.com/*' => Http::response([
                'ok' => false,
                'description' => 'Unauthorized',
            ], 401),
        ]);

        $response = $this->actingAs($this->adminUser)->postJson('/vi/admin/notification-settings/get-zalo-chat-id', [
            'bot_token' => 'invalid_bot_token',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => __('admin.notification_settings.zalo_personal.get_chat_id_error'),
        ]);
    }

    public function test_admin_cannot_enable_notifications_without_active_subscription(): void
    {
        // Disable zalo_oa feature setting
        FeatureSetting::query()->where('feature_code', 'zalo_oa')->update(['is_enabled' => false]);

        $response = $this->actingAs($this->adminUser)->post('/vi/admin/notification-settings', [
            'zalo_oa' => [
                'enabled' => '1',
            ],
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', __('admin.notification_settings.no_package'));
    }

    public function test_admin_cannot_enable_notifications_without_active_subscription_ajax(): void
    {
        // Disable zalo_oa feature setting
        FeatureSetting::query()->where('feature_code', 'zalo_oa')->update(['is_enabled' => false]);

        $response = $this->actingAs($this->adminUser)->postJson('/vi/admin/notification-settings', [
            'zalo_oa' => [
                'enabled' => '1',
            ],
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => __('admin.notification_settings.no_package'),
        ]);
    }

    public function test_admin_can_enable_smtp_without_active_subscription(): void
    {
        // Disable zalo_oa feature setting
        FeatureSetting::query()->where('feature_code', 'zalo_oa')->update(['is_enabled' => false]);

        $response = $this->actingAs($this->adminUser)->post('/vi/admin/notification-settings', [
            'zalo_oa' => [
                'enabled' => '0',
            ],
            'zalo_personal' => [
                'enabled' => '0',
            ],
            'smtp' => [
                'enabled' => '1',
                'host' => 'smtp.test.com',
                'port' => '587',
                'encryption' => 'tls',
                'username' => 'testuser',
                'password' => 'testpass',
                'from_email' => 'shop@test.com',
                'from_name' => 'Shop Name',
                'owner_email' => 'boss@test.com',
            ],
        ]);

        $response->assertRedirect('/vi/admin/notification-settings');
        $response->assertSessionHas('success');

        $setting = ProjectSetting::where('setting_key', 'notification_settings')->first();
        $this->assertNotNull($setting);
        $this->assertTrue($setting->setting_value['smtp']['enabled']);
        $this->assertFalse($setting->setting_value['zalo_oa']['enabled']);
        $this->assertFalse($setting->setting_value['zalo_personal']['enabled']);
    }

    public function test_blank_secret_fields_preserve_existing_encrypted_credentials(): void
    {
        $service = app(NotificationSettingsService::class);
        $service->save([
            'smtp' => [
                'enabled' => true,
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'mailer@example.com',
                'password' => 'original-secret',
                'from_email' => 'mailer@example.com',
                'from_name' => 'Example Shop',
                'owner_email' => 'owner@example.com',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->postJson('/vi/admin/notification-settings', [
            'smtp' => [
                'enabled' => '1',
                'host' => 'smtp.changed.example.com',
                'port' => '587',
                'encryption' => 'tls',
                'username' => 'mailer@example.com',
                'password' => '',
                'from_email' => 'mailer@example.com',
                'from_name' => 'Example Shop',
                'owner_email' => 'owner@example.com',
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $after = ProjectSetting::where('setting_key', 'notification_settings')->first()->setting_value;
        $this->assertStringStartsWith('encrypted:', $after['smtp']['password']);
        $this->assertNotSame('original-secret', $after['smtp']['password']);
        $this->assertSame('original-secret', $service->get()['smtp']['password']);
        $this->assertSame('smtp.changed.example.com', $service->get()['smtp']['host']);
    }

    public function test_notification_settings_page_never_renders_saved_secrets(): void
    {
        app(NotificationSettingsService::class)->save([
            'smtp' => ['password' => 'never-render-this'],
            'zalo_personal' => ['bot_token' => 'never-render-bot-token'],
        ]);

        $response = $this->actingAs($this->adminUser)->get('/vi/admin/notification-settings');

        $response->assertOk();
        $response->assertDontSee('never-render-this');
        $response->assertDontSee('never-render-bot-token');
        $response->assertSee('Đã lưu bảo mật — để trống nếu không đổi');
    }

    public function test_admin_can_send_smtp_test_with_saved_password(): void
    {
        Mail::fake();
        app(NotificationSettingsService::class)->save([
            'smtp' => [
                'enabled' => true,
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'mailer@example.com',
                'password' => 'saved-password',
                'from_email' => 'mailer@example.com',
                'from_name' => 'Example Shop',
                'owner_email' => 'owner@example.com',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->postJson('/vi/admin/notification-settings/test-smtp', [
            'smtp' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'mailer@example.com',
                'password' => '',
                'from_email' => 'mailer@example.com',
                'from_name' => 'Example Shop',
                'owner_email' => 'owner@example.com',
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        Mail::assertSent(TestNotificationMail::class, fn ($mail) => $mail->hasTo('owner@example.com'));
        $this->assertSame('saved-password', config('mail.mailers.admin_smtp.password'));
    }

    public function test_admin_can_send_zalo_personal_test_with_saved_token(): void
    {
        Http::fake([
            'https://bot-api.zaloplatforms.com/*' => Http::response(['ok' => true], 200),
        ]);
        app(NotificationSettingsService::class)->save([
            'zalo_personal' => [
                'enabled' => true,
                'bot_token' => 'saved-bot-token',
                'chat_id' => 'chat-123',
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->postJson('/vi/admin/notification-settings/test-zalo-personal', [
            'zalo_personal' => [
                'bot_token' => '',
                'chat_id' => 'chat-123',
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'botsaved-bot-token/sendMessage')
            && $request['chat_id'] === 'chat-123');
    }

    public function test_admin_can_send_zalo_oa_template_test(): void
    {
        Http::fake([
            'https://business.openapi.zalo.me/*' => Http::response(['error' => 0, 'message' => 'Success'], 200),
        ]);

        $response = $this->actingAs($this->adminUser)->postJson('/vi/admin/notification-settings/test-zalo-oa', [
            'zalo_oa' => [
                'app_id' => 'app-123',
                'secret_key' => 'secret-key',
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'template_id' => 'template-123',
                'template_data' => '{"order_code":"{{order_number}}","amount":"{{grand_total}}"}',
            ],
            'zalo_oa_test_phone' => '0901234567',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        Http::assertSent(fn ($request) => $request->url() === 'https://business.openapi.zalo.me/message/template'
            && $request['phone'] === '84901234567'
            && $request['template_id'] === 'template-123'
            && str_starts_with($request['template_data']['order_code'], 'TEST-'));
    }

    public function test_order_status_dispatches_email_and_zalo_oa(): void
    {
        Mail::fake();
        Http::fake([
            'https://business.openapi.zalo.me/*' => Http::response(['error' => 0], 200),
        ]);
        app(NotificationSettingsService::class)->save([
            'smtp' => [
                'enabled' => true,
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'mailer@example.com',
                'password' => 'secret',
                'from_email' => 'mailer@example.com',
                'from_name' => 'Shop',
                'owner_email' => 'owner@example.com',
            ],
            'zalo_oa' => [
                'enabled' => true,
                'app_id' => 'app-123',
                'secret_key' => 'secret-key',
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'template_id' => 'template-123',
                'template_data' => '{"order_code":"{{order_number}}","status":"{{status}}"}',
            ],
        ]);
        $order = Order::query()->create([
            'order_number' => 'ORD-ZALO-001',
            'customer_name' => 'Nguyễn Văn A',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '0901234567',
            'shipping_address' => 'TP.HCM',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'processing',
            'subtotal' => 100000,
            'discount' => 0,
            'grand_total' => 100000,
        ]);

        app(NotificationService::class)->sendCustomerOrderStatus($order);

        Mail::assertSent(OrderStatusMail::class, fn ($mail) => $mail->hasTo('customer@example.com'));
        Http::assertSent(fn ($request) => $request->url() === 'https://business.openapi.zalo.me/message/template'
            && $request['template_data']['order_code'] === 'ORD-ZALO-001'
            && $request['template_data']['status'] === 'processing');
    }

    public function test_new_order_fans_out_to_every_enabled_notification_channel(): void
    {
        Mail::fake();
        Http::fake([
            'https://business.openapi.zalo.me/*' => Http::response(['error' => 0], 200),
            'https://bot-api.zaloplatforms.com/*' => Http::response(['ok' => true], 200),
        ]);
        app(NotificationSettingsService::class)->save([
            'smtp' => [
                'enabled' => true,
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'mailer@example.com',
                'password' => 'secret',
                'from_email' => 'mailer@example.com',
                'from_name' => 'Shop',
                'owner_email' => 'owner@example.com',
            ],
            'zalo_oa' => [
                'enabled' => true,
                'access_token' => 'oa-access-token',
                'template_id' => 'template-new-order',
                'template_data' => '{"order_code":"{{order_number}}","status":"{{status}}"}',
            ],
            'zalo_personal' => [
                'enabled' => true,
                'bot_token' => 'personal-bot-token',
                'chat_id' => 'admin-chat-id',
            ],
            'dashboard' => [
                'enabled' => true,
                'play_sound' => true,
                'auto_refresh' => true,
            ],
        ]);
        $order = Order::query()->create([
            'order_number' => 'ORD-FANOUT-001',
            'customer_name' => 'Khách hàng thử nghiệm',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '0901234567',
            'shipping_address' => 'TP.HCM',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'subtotal' => 100000,
            'discount' => 0,
            'grand_total' => 100000,
        ]);

        NotificationHelper::sendNewOrderNotification($order);

        Mail::assertSent(OrderStatusMail::class, fn ($mail) => $mail->hasTo('customer@example.com'));
        Mail::assertSent(NewOrderAdminMail::class, fn ($mail) => $mail->hasTo('owner@example.com'));
        Http::assertSent(fn ($request) => $request->url() === 'https://business.openapi.zalo.me/message/template'
            && $request['template_id'] === 'template-new-order');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'botpersonal-bot-token/sendMessage')
            && $request['chat_id'] === 'admin-chat-id');
        $this->actingAs($this->adminUser)
            ->getJson('/vi/admin/notifications/status?since_id=0')
            ->assertOk()
            ->assertJsonPath('new_order.id', $order->id);
    }

    public function test_dashboard_notification_switch_controls_polling_endpoint(): void
    {
        app(NotificationSettingsService::class)->save([
            'dashboard' => [
                'enabled' => false,
                'play_sound' => false,
                'auto_refresh' => false,
            ],
        ]);

        $response = $this->actingAs($this->adminUser)->getJson('/vi/admin/notifications/status?since_id=0');

        $response->assertOk()->assertJson(['enabled' => false]);
    }

    public function test_disabled_smtp_skips_automatic_customer_email_cleanly(): void
    {
        Mail::fake();
        app(NotificationSettingsService::class)->save([
            'smtp' => ['enabled' => false],
        ]);
        $order = Order::query()->create([
            'order_number' => 'ORD-NO-MAIL-001',
            'customer_name' => 'Khách hàng',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '0901234567',
            'shipping_address' => 'TP.HCM',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'pending',
            'subtotal' => 100000,
            'discount' => 0,
            'grand_total' => 100000,
        ]);

        $sent = app(NotificationService::class)->sendCustomerOrderStatus($order);

        $this->assertFalse($sent);
        Mail::assertNothingSent();
    }
}
