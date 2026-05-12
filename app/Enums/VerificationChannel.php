<?php

namespace App\Enums;

enum VerificationChannel: string
{
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
}
