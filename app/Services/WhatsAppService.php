<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a WhatsApp (or WhatsApp Business API) text message when configured.
     * Falls back to logging when no endpoint is configured so local dev still works.
     */
    public function send(string $to, string $message): array
    {
        $url = config('services.whatsapp.message_url');
        $token = config('services.whatsapp.token');

        if (! $url) {
            Log::info('WhatsApp (dev): message not sent — configure services.whatsapp.message_url', [
                'to' => $to,
                'message' => $message,
            ]);

            return ['success' => true, 'skipped' => true];
        }

        $response = Http::timeout(30)
            ->when($token, fn ($http) => $http->withToken($token))
            ->post($url, [
                'to' => $to,
                'message' => $message,
            ]);

        Log::info('WhatsApp API response', [
            'to' => $to,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }
}
