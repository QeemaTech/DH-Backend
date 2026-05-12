@php
    /** @var \App\Models\DigitalOrder $digitalOrder */
    /** @var string $signedUrl */
@endphp

<p>{{ __('Hello') }} {{ $digitalOrder->user_name }},</p>

<p>{{ __('Please confirm your IP address to continue your digital order process.') }}</p>

<p>
    <a href="{{ $signedUrl }}">{{ __('Confirm IP Address') }}</a>
</p>

<p>{{ __('If you did not request this, you can ignore this email.') }}</p>

