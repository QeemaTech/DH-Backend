<?php

namespace App\Repositories;

use App\Models\DigitalProduct;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DigitalProductRepository
{
    public function getAllDigitalProducts(): Collection
    {
        return DigitalProduct::with(['merchant', 'category', 'subCategory'])->latest('id')->get();
    }

    public function getPaginatedDigitalProducts(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = DigitalProduct::query()->with(['merchant', 'category', 'subCategory']);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('product_id', 'like', "%{$search}%")
                    ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$search}%"]);
            });
        }

        if (isset($filters['company_name']) && $filters['company_name'] !== '') {
            $query->where('company_name', (string) $filters['company_name']);
        }

        if (isset($filters['merchant_id']) && $filters['merchant_id'] !== '') {
            $query->where('merchant_id', (int) $filters['merchant_id']);
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (isset($filters['sub_category_id']) && $filters['sub_category_id'] !== '') {
            $query->where('sub_category_id', (int) $filters['sub_category_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (isset($filters['available']) && $filters['available'] !== '') {
            $query->where('is_available', $filters['available'] === 1 || $filters['available'] === '1' || $filters['available'] === true);
        }

        if (array_key_exists('country_id', $filters)) {
            $countryId = $filters['country_id'];
            $query->forCountry($countryId !== null && $countryId !== '' ? (int) $countryId : null);
        }

        $sort = (string) ($filters['sort'] ?? 'latest');
        match ($sort) {
            'oldest' => $query->oldest('id'),
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            default => $query->latest('id'),
        };

        return $query->paginate($perPage);
    }

    public function getDigitalProductById(int $id): ?DigitalProduct
    {
        return DigitalProduct::with(['merchant', 'category', 'subCategory', 'countries'])->find($id);
    }
}

