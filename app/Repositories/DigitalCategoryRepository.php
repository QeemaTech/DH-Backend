<?php

namespace App\Repositories;

use App\Models\DigitalCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DigitalCategoryRepository
{
    /**
     * Get all digital categories.
     */
    public function getAllDigitalCategories(): Collection
    {
        return DigitalCategory::with('lastUpdatedBy')->latest()->get();
    }

    /**
     * Get paginated digital categories with optional filters.
     */
    public function getPaginatedDigitalCategories(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = DigitalCategory::with('lastUpdatedBy');

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$search}%"]);
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        $sort = (string) ($filters['sort'] ?? 'latest');

        match ($sort) {
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderByRaw("JSON_EXTRACT(name, '$.en') ASC"),
            'name_desc' => $query->orderByRaw("JSON_EXTRACT(name, '$.en') DESC"),
            default => $query->latest(),
        };

        return $query->paginate($perPage);
    }

    /**
     * Get digital category by id.
     */
    public function getDigitalCategoryById(int $id): ?DigitalCategory
    {
        return DigitalCategory::with('lastUpdatedBy','products')->find($id);
    }

    /**
     * Get active digital categories.
     */
    public function getActiveDigitalCategories(): Collection
    {
        return DigitalCategory::active()->with('lastUpdatedBy')->latest()->get();
    }

    /**
     * Create a new digital category.
     */
    public function create(array $data): DigitalCategory
    {
        return DigitalCategory::create($data);
    }

    /**
     * Update an existing digital category.
     */
    public function update(DigitalCategory $digitalCategory, array $data): bool
    {
        return $digitalCategory->update($data);
    }

    /**
     * Delete a digital category.
     */
    public function delete(DigitalCategory $digitalCategory): bool
    {
        return (bool) $digitalCategory->delete();
    }
}
