<?php

namespace App\Repositories;

use App\Models\DigitalSubCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DigitalSubCategoryRepository
{
    /**
     * Get all digital sub categories.
     */
    public function getAllDigitalSubCategories(): Collection
    {
        return DigitalSubCategory::with(['digitalCategory', 'lastUpdatedBy'])->latest()->get();
    }

    /**
     * Get paginated digital sub categories with optional filters.
     */
    public function getPaginatedDigitalSubCategories(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = DigitalSubCategory::with(['digitalCategory', 'lastUpdatedBy']);

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

        if (! empty($filters['digital_category_id'])) {
            $query->where('digital_category_id', (int) $filters['digital_category_id']);
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
     * Get digital sub category by id.
     */
    public function getDigitalSubCategoryById(int $id): ?DigitalSubCategory
    {
        return DigitalSubCategory::with(['digitalCategory', 'lastUpdatedBy'])->find($id);
    }

    /**
     * Create a new digital sub category.
     */
    public function create(array $data): DigitalSubCategory
    {
        return DigitalSubCategory::create($data);
    }

    /**
     * Update an existing digital sub category.
     */
    public function update(DigitalSubCategory $digitalSubCategory, array $data): bool
    {
        return $digitalSubCategory->update($data);
    }

    /**
     * Delete a digital sub category.
     */
    public function delete(DigitalSubCategory $digitalSubCategory): bool
    {
        return (bool) $digitalSubCategory->delete();
    }
}
