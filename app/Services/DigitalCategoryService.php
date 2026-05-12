<?php

namespace App\Services;

use App\Models\DigitalCategory;
use App\Repositories\DigitalCategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DigitalCategoryService
{
    public function __construct(protected DigitalCategoryRepository $digitalCategoryRepository) {}

    /**
     * Get all digital categories.
     */
    public function getAllDigitalCategories(): Collection
    {
        return $this->digitalCategoryRepository->getAllDigitalCategories();
    }

    /**
     * Get paginated digital categories with optional filters.
     */
    public function getPaginatedDigitalCategories(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->digitalCategoryRepository->getPaginatedDigitalCategories($perPage, $filters);
    }

    /**
     * Get digital category by id.
     */
    public function getDigitalCategoryById(int $id): ?DigitalCategory
    {
        return $this->digitalCategoryRepository->getDigitalCategoryById($id);
    }

    /**
     * Get active digital categories.
     */
    public function getActiveDigitalCategories(): Collection
    {
        return $this->digitalCategoryRepository->getActiveDigitalCategories();
    }

    /**
     * Create a new digital category.
     */
    public function createDigitalCategory(array $data): DigitalCategory
    {
        DB::beginTransaction();

        try {
            $data['slug'] = $this->generateUniqueSlug($data['name'] ?? null);

            if (isset($data['image']) && $data['image']) {
                $data['image'] = $data['image']->store('digital-categories', 'public');
            }

            if (isset($data['thumbnail']) && $data['thumbnail']) {
                $data['thumbnail'] = $data['thumbnail']->store('digital-categories/thumbnails', 'public');
            }

            $digitalCategory = $this->digitalCategoryRepository->create($data);
            DB::commit();

            return $digitalCategory;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing digital category.
     */
    public function updateDigitalCategory(DigitalCategory $digitalCategory, array $data): DigitalCategory
    {
        DB::beginTransaction();

        try {
            if (array_key_exists('name', $data) || empty($digitalCategory->slug)) {
                $data['slug'] = $this->generateUniqueSlug($data['name'] ?? null, $digitalCategory->id);
            }

            if (isset($data['image']) && $data['image']) {
                $oldImage = $digitalCategory->getOriginal('image');
                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }
                $data['image'] = $data['image']->store('digital-categories', 'public');
            }

            if (isset($data['thumbnail']) && $data['thumbnail']) {
                $oldThumbnail = $digitalCategory->getOriginal('thumbnail');
                if ($oldThumbnail && Storage::disk('public')->exists($oldThumbnail)) {
                    Storage::disk('public')->delete($oldThumbnail);
                }
                $data['thumbnail'] = $data['thumbnail']->store('digital-categories/thumbnails', 'public');
            }

            $this->digitalCategoryRepository->update($digitalCategory, $data);
            $digitalCategory->refresh();
            DB::commit();

            return $digitalCategory;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete an existing digital category.
     */
    public function deleteDigitalCategory(DigitalCategory $digitalCategory): bool
    {
        DB::beginTransaction();

        try {
            $oldImage = $digitalCategory->getOriginal('image');
            if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }

            $oldThumbnail = $digitalCategory->getOriginal('thumbnail');
            if ($oldThumbnail && Storage::disk('public')->exists($oldThumbnail)) {
                Storage::disk('public')->delete($oldThumbnail);
            }

            $deleted = $this->digitalCategoryRepository->delete($digitalCategory);
            DB::commit();

            return $deleted;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate a unique slug for digital categories.
     */
    protected function generateUniqueSlug(mixed $name, ?int $ignoreId = null): string
    {
        $source = $this->resolveSlugSource($name);
        $baseSlug = Str::slug($source);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'digital-category';

        $slug = $baseSlug;
        $counter = 1;

        while (
            DigitalCategory::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Resolve best available name for slug generation.
     */
    protected function resolveSlugSource(mixed $name): string
    {
        if (is_array($name)) {
            return (string) ($name['en'] ?? $name['ar'] ?? reset($name) ?? '');
        }

        return (string) $name;
    }
}
