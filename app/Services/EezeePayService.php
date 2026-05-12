<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EezeePayService
{
    protected ?string $runtimeToken = null;

    /**
     * @return array{username_key: string, password_key: string, username: string, password: string}
     */
    protected function resolveLoginCredentials(): array
    {
        $config = $this->config();

        $usernameKey = (string) ($config['login_username_key'] ?? 'username');
        $passwordKey = (string) ($config['login_password_key'] ?? 'password');

        $username = (string) ($config['login_username'] ?? '');
        if ($username === '') {
            $username = (string) ($config['email'] ?? '');
        }
        if ($username === '') {
            $username = (string) ($config['phone'] ?? '');
        }

        $password = (string) ($config['password'] ?? '');

        return [
            'username_key' => $usernameKey !== '' ? $usernameKey : 'username',
            'password_key' => $passwordKey !== '' ? $passwordKey : 'password',
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $credentials  Override body for POST /login (e.g. phone-based login).
     * @return array<string, mixed>
     */
    public function login(?array $credentials = null): array
    {
        if ($credentials === null) {
            $resolved = $this->resolveLoginCredentials();
            if ($resolved['username'] === '' || $resolved['password'] === '') {
                throw new RuntimeException('Eezee Pay login requires username + password. Set EEZEEP_PASSWORD, and one of EEZEEP_LOGIN_USERNAME / EEZEEP_EMAIL / EEZEEP_PHONE.');
            }

            $credentials = [
                $resolved['username_key'] => $resolved['username'],
                $resolved['password_key'] => $resolved['password'],
            ];
        }

        $data = $this->send('POST', '/login', [], $credentials, null, false);
        $this->persistTokenFromResponse($data);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function regenerateToken(?string $token = null): array
    {
        $path = (string) ($this->config()['regenerate_token_path'] ?? '/token/regenerate');
        $data = $this->send('POST', $path, [], [], $token, true);
        $this->persistTokenFromResponse($data);

        return $data;
    }

    /**
     * Clears the token kept in memory and in cache (does not change .env).
     */
    public function forgetRememberedToken(): void
    {
        $this->runtimeToken = null;
        Cache::forget($this->tokenCacheKey());
    }

    public function setRuntimeToken(?string $token): void
    {
        $this->runtimeToken = $token;
    }

    /**
     * @return array<string, mixed>
     */
    public function categories(?string $token = null): array
    {
        return $this->send('GET', '/categories', [], [], $token);
    }

    /**
     * @return array<string, mixed>
     */
    public function showCategory(int $categoryId, ?string $token = null): array
    {
        return $this->send('GET', '/show', ['category_id' => $categoryId], [], $token);
    }

    /**
     * @return array<string, mixed>
     */
    public function productsByCategory(int $categoryId, ?string $token = null): array
    {
        return $this->send('GET', '/products', ['category_id' => $categoryId], [], $token);
    }

    /**
     * @return array<string, mixed>
     */
    public function showProduct(int $productId, ?string $token = null): array
    {
        return $this->send('GET', '/show', ['product_id' => $productId], [], $token);
    }

    /**
     * @return array<string, mixed>
     */
    public function checkProductAvailability(int $productId, int $quantity, ?string $token = null): array
    {
        return $this->send('POST', '/products/check', [], ['product_id' => $productId,
            'qty' => $quantity, ], $token);
    }

    /**
     * @return array<string, mixed>
     */
    public function balance(?string $token = null): array
    {
        return $this->send('GET', '/me/balance', [], [], $token);
    }

    /**
     * @param  array<int, array{qty: int, product_id: int}>|array{qty: int, product_id: int}  $cart
     * @return array<string, mixed>
     */
    public function createOrder(?string $paymentRef, array $cart, ?string $token = null): array
    {
        $body = ['cart' => $cart];
        if ($paymentRef !== null) {
            $body['payment_ref'] = $paymentRef;
        }

        // check product availability
        // $res = $this->checkProductAvailability($cart[0]['product_id'], $cart[0]['qty'], $token);

        // dd($res);
        // if ($res['data']['is_available'] !== true) {
        //     throw new RuntimeException('Product is not available.');
        // }

        return $this->send('POST', '/order/create', [], $body, $token);
    }

    /**
     * @return array<string, mixed>
     */
    public function showOrder(string $orderCode, ?string $token = null): array
    {
        return $this->send('GET', '/order/show', ['code' => $orderCode], [], $token);
    }

    /**
     * @return array<string, mixed>
     */
    protected function send(string $method, string $path, array $query, array $body, ?string $token, bool $authenticate = true): array
    {
        return $this->sendWithOptionalAuthRefresh($method, $path, $query, $body, $token, $authenticate, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sendWithOptionalAuthRefresh(
        string $method,
        string $path,
        array $query,
        array $body,
        ?string $token,
        bool $authenticate,
        bool $allowAuthRefresh,
    ): array {
        if ($authenticate
            && $path !== '/login'
            && $token === null
            && $this->shouldFreshLoginBeforeAuthenticatedRequest()) {
            $this->forgetRememberedToken();
            $this->login();
        }

        $resolvedToken = $authenticate ? $this->resolveTokenForRequest($token) : '';
        $url = $this->baseUrl().$path;

        $pending = Http::acceptJson()
            ->timeout($this->timeout())
            ->connectTimeout($this->connectTimeout())
            ->withHeaders($this->authHeaders($resolvedToken));

        /** @var \Illuminate\Http\Client\Response $response */
        $response = match (strtoupper($method)) {
            'GET' => $pending->get($url, $query),
            'POST' => $pending->asJson()->post($url, $body),
            default => throw new RuntimeException("Eezee Pay unsupported HTTP method: {$method}"),
        };

        $context = $method.' '.$path;

        if ($response->failed()
            && $allowAuthRefresh
            && $authenticate
            && $path !== '/login'
            && $this->shouldAttemptAuthRefresh($response)) {
            $this->forgetRememberedToken();
            Log::notice('Eezee Pay token rejected; cleared cache and attempting fresh login.', ['context' => $context]);

            if ($this->canAutoLoginAfterInvalidToken()) {
                try {
                    $this->login();
                } catch (\Throwable $e) {
                    Log::notice('Eezee Pay login after invalid token threw.', [
                        'context' => $context,
                        'message' => $e->getMessage(),
                    ]);

                    return $this->decodeResponse($response, $context);
                }

                if (! $this->hasUsableTokenForAuthenticatedRequests()) {
                    Log::warning('Eezee Pay login succeeded but no token was extracted from the response.', [
                        'context' => $context,
                        'hint' => 'Vendor JSON may use a different key; extend extractTokenFromResponse if needed.',
                    ]);

                    return $this->decodeResponse($response, $context);
                }

                return $this->sendWithOptionalAuthRefresh($method, $path, $query, $body, null, $authenticate, false);
            }
        }

        return $this->decodeResponse($response, $context);
    }

    protected function shouldAttemptAuthRefresh(Response $response): bool
    {
        $status = $response->status();
        if ($status !== 401 && $status !== 403) {
            return false;
        }

        if ($this->responseIndicatesInvalidToken($response)) {
            return true;
        }

        /**
         * Some Eezee Pay environments respond with a generic 401/403 without a helpful body.
         * If we have password credentials, attempt a single refresh to recover.
         */
        return $this->canAutoLoginAfterInvalidToken();
    }

    protected function responseIndicatesInvalidToken(Response $response): bool
    {
        $status = $response->status();
        if ($status !== 401 && $status !== 403) {
            return false;
        }

        $lower = strtolower($response->body());

        return str_contains($lower, 'token is invalid')
            || str_contains($lower, 'auth error')
            || str_contains($lower, 'token has been expired')
            || str_contains($lower, 'token has expired');
    }

    protected function shouldFreshLoginBeforeAuthenticatedRequest(): bool
    {
        if (! $this->hasPasswordCredentials()) {
            return false;
        }

        return filter_var($this->config()['fresh_login_each_request'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    protected function hasPasswordCredentials(): bool
    {
        $email = (string) ($this->config()['email'] ?? '');
        $password = (string) ($this->config()['password'] ?? '');

        return $email !== '' && $password !== '';
    }

    protected function canAutoLoginAfterInvalidToken(): bool
    {
        if (! (bool) ($this->config()['auto_login_on_auth_error'] ?? true)) {
            return false;
        }

        $email = (string) ($this->config()['email'] ?? '');
        $password = (string) ($this->config()['password'] ?? '');

        return $email !== '' && $password !== '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeResponse(Response $response, string $context): array
    {
        if ($response->failed()) {
            Log::warning('Eezee Pay API request failed', [
                'context' => $context,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                "Eezee Pay {$context} failed with HTTP {$response->status()}."
            );
        }

        /** @var array<string, mixed>|null $json */
        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        /** @var array<string, mixed> $config */
        $config = config('services.eezee_pay', []);

        return $config;
    }

    protected function baseUrl(): string
    {
        $base = rtrim((string) ($this->config()['base_url'] ?? ''), '/');
        if ($base === '') {
            throw new RuntimeException('Eezee Pay base URL is not configured (EEZEEP_BASE_URL).');
        }

        return $base;
    }

    protected function timeout(): int
    {
        return (int) ($this->config()['timeout'] ?? 30);
    }

    protected function connectTimeout(): int
    {
        return (int) ($this->config()['connect_timeout'] ?? 15);
    }

    /**
     * @return array<string, string>
     */
    protected function authHeaders(string $token): array
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        $userAgent = (string) ($this->config()['user_agent'] ?? '');
        if ($userAgent !== '') {
            $headers['User-Agent'] = $userAgent;
        }

        if ($token === '') {
            return $headers;
        }

        $headerName = (string) ($this->config()['auth_header'] ?? 'Authorization');
        $scheme = $this->config()['auth_scheme'];
        $schemeStr = $scheme === null || $scheme === '' ? '' : trim((string) $scheme).' ';
        $headers[$headerName] = $schemeStr.$token;

        return $headers;
    }

    protected function resolveTokenForRequest(?string $explicit): string
    {
        if ($explicit !== null) {
            return $explicit;
        }

        if ($this->runtimeToken !== null && $this->runtimeToken !== '') {
            return $this->runtimeToken;
        }

        $cached = Cache::get($this->tokenCacheKey());
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return (string) ($this->config()['token'] ?? '');
    }

    protected function tokenCacheKey(): string
    {
        return (string) ($this->config()['token_cache_key'] ?? 'eezee_pay.access_token');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function persistTokenFromResponse(array $data): void
    {
        $token = $this->extractTokenFromResponse($data);
        if ($token === null || $token === '') {
            return;
        }

        $this->runtimeToken = $token;

        if (! (bool) ($this->config()['remember_token_in_cache'] ?? true)) {
            return;
        }

        $ttl = (int) ($this->config()['token_cache_ttl'] ?? 3600);
        Cache::put($this->tokenCacheKey(), $token, now()->addSeconds(max(60, $ttl)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractTokenFromResponse(array $data): ?string
    {
        $candidates = [
            data_get($data, 'token'),
            data_get($data, 'access_token'),
            data_get($data, 'data.token'),
            data_get($data, 'data.access_token'),
            data_get($data, 'result.token'),
            data_get($data, 'result.access_token'),
            data_get($data, 'data.data.token'),
            data_get($data, 'data.data.access_token'),
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $this->extractTokenFromNestedArray($data, 0);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected function extractTokenFromNestedArray(array $node, int $depth): ?string
    {
        if ($depth > 8) {
            return null;
        }

        $tokenKeys = [
            'token',
            'access_token',
            'accesstoken',
            'access-token',
            'auth_token',
            'api_token',
            'bearer_token',
        ];

        foreach ($node as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (in_array(strtolower($key), $tokenKeys, true) && is_string($value) && $value !== '') {
                return $value;
            }
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $found = $this->extractTokenFromNestedArray($value, $depth + 1);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    protected function hasUsableTokenForAuthenticatedRequests(): bool
    {
        if ($this->runtimeToken !== null && $this->runtimeToken !== '') {
            return true;
        }

        $cached = Cache::get($this->tokenCacheKey());

        return is_string($cached) && $cached !== '';
    }
}
// {
//     "message": "Provider order placed (test mode).",
//     "order_id": 1,
//     "provider_response": {
//         "success": true,
//         "status": 200,
//         "message": "success",
//         "data": {
//             "code": "1651611980688",
//             "qty": 1,
//             "total": 0.614,
//             "status": "Done",
//             "currency": "KWD",
//             "payment": {
//                 "ref_id": "TEST-DIGITAL-ORDER-1",
//                 "transaction_date": "2026-04-16 13:51:07",
//                 "trans_id": "Ks-165vTgo9891776336667421"
//             },
//             "items": [
//                 {
//                     "product_id": 3,
//                     "product_name_en": "Itunes 2$ USA",
//                     "product_name_ar": "Itunes 2$ USA",
//                     "serial": "Z2000001-2-1001",
//                     "unit_price": 0.614,
//                     "pin": null
//                 }
//             ]
//         }
//     }
// }
