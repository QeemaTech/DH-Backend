<?php

namespace App\Support;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryHeaderResolver
{
    /**
     * @return array{country: Country|null, error: string|null}
     */
    public function resolve(Request $request): array
    {
        $raw = $this->readHeaderValue($request);
        if ($raw === null || $raw === '') {
            return [
                'country' => null,
                'error' => __('Country header is required.'),
            ];
        }

        $raw = trim($raw);

        $country = null;
        if (ctype_digit($raw)) {
            $country = Country::query()
                ->where('id', (int) $raw)
                ->where('is_active', true)
                ->first();
        } else {
            $country = Country::query()
                ->whereRaw('LOWER(code) = ?', [strtolower($raw)])
                ->where('is_active', true)
                ->first();
        }

        if (! $country) {
            return [
                'country' => null,
                'error' => __('Invalid country header.'),
            ];
        }

        return [
            'country' => $country,
            'error' => null,
        ];
    }

    private function readHeaderValue(Request $request): ?string
    {
        foreach (['X-Country', 'X-Country-Code', 'Country', 'country'] as $name) {
            $value = (string) $request->header($name, '');
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}

