<?php

namespace App\Http\Middleware;

use App\Support\CountryHeaderResolver;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCountryHeader
{
    public function __construct(
        protected CountryHeaderResolver $resolver
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $result = $this->resolver->resolve($request);
        if (! $result['country']) {
            return new JsonResponse([
                'success' => false,
                'message' => $result['error'] ?? __('Invalid country header.'),
                'errors' => [
                    'country_header' => [$result['error'] ?? __('Invalid country header.')],
                ],
            ], 422);
        }

        $request->attributes->set('resolved_country', $result['country']);
        $request->attributes->set('resolved_country_id', (int) $result['country']->id);

        return $next($request);
    }
}

