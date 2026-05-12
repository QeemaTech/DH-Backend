<?php

namespace Tests\Feature;

use App\Services\EezeePayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EezeePayServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if ($this->app->bound(EezeePayService::class)) {
            $this->app->make(EezeePayService::class)->forgetRememberedToken();
        }

        parent::tearDown();
    }

    public function test_login_posts_json_to_configured_base_url(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.email', 'u@example.com');
        config()->set('services.eezee_pay.password', 'secret');
        config()->set('services.eezee_pay.token', '');

        Http::fake(function ($request) {
            $this->assertSame('https://sandbox.eezee-pay.com/api/v1/login', (string) $request->url());
            $this->assertSame('POST', $request->method());
            $this->assertSame('application/json', $request->header('Content-Type')[0] ?? '');
            $data = $request->data();
            $this->assertSame('u@example.com', $data['username'] ?? null);
            $this->assertSame('secret', $data['password'] ?? null);

            return Http::response(['token' => 'jwt-here', 'status' => true], 200);
        });

        $resp = $this->app->make(EezeePayService::class)->login();
        $this->assertSame('jwt-here', $resp['token'] ?? null);
    }

    public function test_login_does_not_send_stale_authorization_header(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.email', 'u@example.com');
        config()->set('services.eezee_pay.password', 'secret');
        config()->set('services.eezee_pay.token', 'stale-jwt');

        Http::fake(function ($request) {
            $this->assertSame([], $request->header('Authorization') ?? []);

            return Http::response(['token' => 'new-from-login'], 200);
        });

        $this->app->make(EezeePayService::class)->login();
    }

    public function test_endpoints_after_login_use_fresh_token_from_response(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.email', 'u@example.com');
        config()->set('services.eezee_pay.password', 'secret');
        config()->set('services.eezee_pay.token', '');

        Http::fake(function ($request) {
            $url = (string) $request->url();
            if (str_contains($url, '/login')) {
                $this->assertSame([], $request->header('Authorization') ?? []);

                return Http::response(['token' => 'fresh-jwt'], 200);
            }
            if (str_contains($url, '/me/balance')) {
                $this->assertSame('Bearer fresh-jwt', $request->header('Authorization')[0] ?? '');

                return Http::response(['balance' => 99], 200);
            }

            $this->fail('Unexpected request URL: '.$url);
        });

        $eezee = $this->app->make(EezeePayService::class);
        $eezee->login();
        $balance = $eezee->balance();
        $this->assertSame(99, $balance['balance'] ?? null);
    }

    public function test_balance_sends_bearer_token_by_default(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.token', 'my-jwt');

        Http::fake(function ($request) {
            $this->assertSame('https://sandbox.eezee-pay.com/api/v1/me/balance', (string) $request->url());
            $this->assertSame('Bearer my-jwt', $request->header('Authorization')[0] ?? '');

            return Http::response(['balance' => 10.5], 200);
        });

        $resp = $this->app->make(EezeePayService::class)->balance();
        $this->assertSame(10.5, $resp['balance'] ?? null);
    }

    public function test_categories_auto_logs_in_once_when_token_is_invalid(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.email', 'u@example.com');
        config()->set('services.eezee_pay.password', 'secret');
        config()->set('services.eezee_pay.token', 'expired-jwt');
        config()->set('services.eezee_pay.auto_login_on_auth_error', true);
        config()->set('services.eezee_pay.fresh_login_each_request', false);

        $categoryCalls = 0;
        Http::fake(function ($request) use (&$categoryCalls) {
            $url = (string) $request->url();
            if (str_contains($url, '/login')) {
                return Http::response(['token' => 'fresh-after-403'], 200);
            }
            if (str_contains($url, '/categories')) {
                $categoryCalls++;
                if ($categoryCalls === 1) {
                    $this->assertSame('Bearer expired-jwt', $request->header('Authorization')[0] ?? '');

                    return Http::response([
                        'success' => false,
                        'status' => 403,
                        'message' => 'Auth Error: The token is invalid',
                    ], 403);
                }
                $this->assertSame('Bearer fresh-after-403', $request->header('Authorization')[0] ?? '');

                return Http::response(['data' => ['ok' => true]], 200);
            }

            $this->fail('Unexpected URL: '.$url);
        });

        $resp = $this->app->make(EezeePayService::class)->categories();
        $this->assertTrue((bool) data_get($resp, 'data.ok'));
        $this->assertSame(2, $categoryCalls);
    }

    public function test_categories_uses_get_without_content_type_body(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.token', 'tok');

        Http::fake(function ($request) {
            $this->assertStringStartsWith('https://sandbox.eezee-pay.com/api/v1/categories', (string) $request->url());
            $this->assertSame('GET', $request->method());
            $this->assertSame('Bearer tok', $request->header('Authorization')[0] ?? '');

            return Http::response(['data' => []], 200);
        });

        $resp = $this->app->make(EezeePayService::class)->categories();
        $this->assertSame([], $resp['data'] ?? null);
    }

    public function test_show_category_passes_query_parameter(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.token', 'x');

        Http::fake(function ($request) {
            $this->assertStringContainsString('category_id=7', (string) $request->url());

            return Http::response(['id' => 7], 200);
        });

        $resp = $this->app->make(EezeePayService::class)->showCategory(7);
        $this->assertSame(7, $resp['id'] ?? null);
    }

    public function test_create_order_posts_cart_and_payment_ref(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.token', 'x');

        Http::fake(function ($request) {
            $this->assertSame('https://sandbox.eezee-pay.com/api/v1/order/create', (string) $request->url());
            $data = $request->data();
            $this->assertSame('ref-1', $data['payment_ref'] ?? null);
            $this->assertSame(1, $data['cart']['qty'] ?? null);
            $this->assertSame(99, $data['cart']['product_id'] ?? null);

            return Http::response(['ok' => true], 200);
        });

        $resp = $this->app->make(EezeePayService::class)->createOrder('ref-1', [
            'qty' => 1,
            'product_id' => 99,
        ]);
        $this->assertTrue((bool) ($resp['ok'] ?? false));
    }

    public function test_authenticated_request_performs_login_first_when_email_password_configured(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.email', 'u@example.com');
        config()->set('services.eezee_pay.password', 'secret');
        config()->set('services.eezee_pay.token', 'ignored-from-env');
        config()->set('services.eezee_pay.fresh_login_each_request', true);

        $order = [];
        Http::fake(function ($request) use (&$order) {
            $url = (string) $request->url();
            if (str_contains($url, '/login')) {
                $order[] = 'login';

                return Http::response(['token' => 'from-login'], 200);
            }
            if (str_contains($url, '/categories')) {
                $order[] = 'categories';
                $this->assertSame('Bearer from-login', $request->header('Authorization')[0] ?? '');

                return Http::response(['data' => []], 200);
            }

            $this->fail('Unexpected URL: '.$url);
        });

        $this->app->make(EezeePayService::class)->categories();
        $this->assertSame(['login', 'categories'], $order);
    }

    public function test_explicit_token_skips_fresh_login_even_with_password_credentials(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.email', 'u@example.com');
        config()->set('services.eezee_pay.password', 'secret');
        config()->set('services.eezee_pay.token', '');

        $loginHits = 0;
        Http::fake(function ($request) use (&$loginHits) {
            $url = (string) $request->url();
            if (str_contains($url, '/login')) {
                $loginHits++;

                return Http::response(['token' => 'x'], 200);
            }
            if (str_contains($url, '/me/balance')) {
                $this->assertSame('Bearer caller-supplied', $request->header('Authorization')[0] ?? '');

                return Http::response(['balance' => 1], 200);
            }

            $this->fail('Unexpected URL: '.$url);
        });

        $this->app->make(EezeePayService::class)->balance('caller-supplied');
        $this->assertSame(0, $loginHits);
    }

    public function test_categories_auto_logs_in_once_when_token_returns_401_expired(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.email', 'u@example.com');
        config()->set('services.eezee_pay.password', 'secret');
        config()->set('services.eezee_pay.token', 'expired-jwt');
        config()->set('services.eezee_pay.auto_login_on_auth_error', true);
        config()->set('services.eezee_pay.fresh_login_each_request', false);

        $categoryCalls = 0;
        Http::fake(function ($request) use (&$categoryCalls) {
            $url = (string) $request->url();
            if (str_contains($url, '/login')) {
                return Http::response(['token' => 'fresh-after-401'], 200);
            }
            if (str_contains($url, '/categories')) {
                $categoryCalls++;
                if ($categoryCalls === 1) {
                    $this->assertSame('Bearer expired-jwt', $request->header('Authorization')[0] ?? '');

                    return Http::response([
                        'success' => false,
                        'status' => 401,
                        'message' => 'Auth Error: The token has been expired',
                    ], 401);
                }
                $this->assertSame('Bearer fresh-after-401', $request->header('Authorization')[0] ?? '');

                return Http::response(['data' => ['ok' => true]], 200);
            }

            $this->fail('Unexpected URL: '.$url);
        });

        $resp = $this->app->make(EezeePayService::class)->categories();
        $this->assertTrue((bool) data_get($resp, 'data.ok'));
        $this->assertSame(2, $categoryCalls);
    }

    public function test_auto_retry_after_401_uses_token_nested_inside_login_response(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.email', 'u@example.com');
        config()->set('services.eezee_pay.password', 'secret');
        config()->set('services.eezee_pay.token', 'expired-jwt');
        config()->set('services.eezee_pay.auto_login_on_auth_error', true);
        config()->set('services.eezee_pay.fresh_login_each_request', false);

        Http::fake(function ($request) {
            $url = (string) $request->url();
            if (str_contains($url, '/login')) {
                return Http::response([
                    'success' => true,
                    'data' => ['payload' => ['access_token' => 'from-nested-login']],
                ], 200);
            }
            if (str_contains($url, '/categories')) {
                $auth = $request->header('Authorization')[0] ?? '';
                if ($auth === 'Bearer expired-jwt') {
                    return Http::response([
                        'success' => false,
                        'status' => 401,
                        'message' => 'Auth Error: The token has been expired',
                    ], 401);
                }
                if ($auth === 'Bearer from-nested-login') {
                    return Http::response(['data' => ['recovered' => true]], 200);
                }
            }

            $this->fail('Unexpected URL: '.$url);
        });

        $resp = $this->app->make(EezeePayService::class)->categories();
        $this->assertTrue((bool) data_get($resp, 'data.recovered'));
    }
}
