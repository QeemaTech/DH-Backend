<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $iid;

    protected string $uid;

    protected string $password;

    protected string $sender;

    protected string $baseUrl;

    public function __construct()
    {
        $this->iid = config('services.sms.iid');
        $this->uid = config('services.sms.uid');
        $this->password = config('services.sms.password');
        $this->sender = config('services.sms.sender');
        $this->baseUrl = config('services.sms.base_url');
    }

    public function send(string $to, string $message): array
    {
        $to = $this->normalizeNumbers($to);

        $response = Http::timeout(30)->get($this->baseUrl, [
            'IID' => $this->iid,
            'UID' => $this->uid,
            'P' => $this->password,
            'S' => $this->sender,
            'G' => $to,
            'M' => $message,
        ]);

        $body = trim($response->body());
        $statusCode = substr($body, 0, 2);

        Log::info('SMS Response', [
            'to' => $to,
            'response' => $body,
            'status' => $statusCode,
        ]);

        return [
            'success' => $statusCode === '00',
            'status' => $statusCode,
            'response' => $body,
        ];
    }

    private function normalizeNumbers(string $input, string $default_country_code = '20'): string
    {
        $parts = preg_split('/\s*,\s*/', trim($input));
        $norm = [];

        foreach ($parts as $p) {
            $digits = preg_replace('/\D+/', '', $p);
            if ($digits === '') {
                continue;
            }

            if (strpos($digits, '00') === 0) {
                $digits = substr($digits, 2);
            }

            if (strpos($digits, '0') === 0 && strlen($digits) > 1) {
                if (strpos($digits, $default_country_code) !== 0) {
                    $digits = $default_country_code.substr($digits, 1);
                }
            }

            if (strlen($digits) == 10 && $digits[0] == '1') {
                $digits = $default_country_code.$digits;
            }

            if (strlen($digits) < 11 || strlen($digits) > 15) {
                continue;
            }

            $norm[] = $digits;
        }

        return implode(',', $norm);
    }
}
