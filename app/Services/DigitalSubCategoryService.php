<?php

namespace App\Services;

use App\Models\DigitalSubCategory;
use App\Repositories\DigitalSubCategoryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DigitalSubCategoryService
{
    public function __construct(protected DigitalSubCategoryRepository $digitalSubCategoryRepository) {}

    public function getAllDigitalSubCategories(): Collection
    {
        return $this->digitalSubCategoryRepository->getAllDigitalSubCategories();
    }

    public function getPaginatedDigitalSubCategories(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->digitalSubCategoryRepository->getPaginatedDigitalSubCategories($perPage, $filters);
    }

    public function getDigitalSubCategoryById(int $id): ?DigitalSubCategory
    {
        return $this->digitalSubCategoryRepository->getDigitalSubCategoryById($id);
    }

    public function createDigitalSubCategory(array $data): DigitalSubCategory
    {
        DB::beginTransaction();

        try {
            $data['slug'] = $this->generateUniqueSlug($data['name'] ?? null);

            if (isset($data['image']) && $data['image']) {
                $data['image'] = $data['image']->store('digital-sub-categories', 'public');
            }

            if (isset($data['thumbnail']) && $data['thumbnail']) {
                $data['thumbnail'] = $data['thumbnail']->store('digital-sub-categories/thumbnails', 'public');
            }

            $digitalSubCategory = $this->digitalSubCategoryRepository->create($data);
            DB::commit();

            return $digitalSubCategory;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateDigitalSubCategory(DigitalSubCategory $digitalSubCategory, array $data): DigitalSubCategory
    {
        DB::beginTransaction();

        try {
            if (array_key_exists('name', $data) || empty($digitalSubCategory->slug)) {
                $data['slug'] = $this->generateUniqueSlug($data['name'] ?? null, $digitalSubCategory->id);
            }

            if (isset($data['image']) && $data['image']) {
                $oldImage = $digitalSubCategory->getOriginal('image');
                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }
                $data['image'] = $data['image']->store('digital-sub-categories', 'public');
            }

            if (isset($data['thumbnail']) && $data['thumbnail']) {
                $oldThumbnail = $digitalSubCategory->getOriginal('thumbnail');
                if ($oldThumbnail && Storage::disk('public')->exists($oldThumbnail)) {
                    Storage::disk('public')->delete($oldThumbnail);
                }
                $data['thumbnail'] = $data['thumbnail']->store('digital-sub-categories/thumbnails', 'public');
            }

            $this->digitalSubCategoryRepository->update($digitalSubCategory, $data);
            $digitalSubCategory->refresh();
            DB::commit();

            return $digitalSubCategory;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteDigitalSubCategory(DigitalSubCategory $digitalSubCategory): bool
    {
        DB::beginTransaction();

        try {
            $oldImage = $digitalSubCategory->getOriginal('image');
            if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                Storage::disk('public')->delete($oldImage);
            }

            $oldThumbnail = $digitalSubCategory->getOriginal('thumbnail');
            if ($oldThumbnail && Storage::disk('public')->exists($oldThumbnail)) {
                Storage::disk('public')->delete($oldThumbnail);
            }

            $deleted = $this->digitalSubCategoryRepository->delete($digitalSubCategory);
            DB::commit();

            return $deleted;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function generateUniqueSlug(mixed $name, ?int $ignoreId = null): string
    {
        $source = $this->resolveSlugSource($name);
        $baseSlug = Str::slug($source);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'digital-sub-category';

        $slug = $baseSlug;
        $counter = 1;

        while (
            DigitalSubCategory::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function resolveSlugSource(mixed $name): string
    {
        if (is_array($name)) {
            return (string) ($name['en'] ?? $name['ar'] ?? reset($name) ?? '');
        }

        return (string) $name;
    }
}
