<?php

namespace App\Http\Controllers;

use App\Services\SmsService;

class HomeController extends Controller
{
    public function sendSms(SmsService $sms)
    {
        $result = $sms->send('96599428171', 'Hello, this is a test SMS from your application.');

        if ($result['success']) {
            return 'تم الإرسال ✅';
        }

        return 'فشل ❌ - '.$result['status'];
    }
}
