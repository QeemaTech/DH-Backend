<?php

namespace App\Services;

use App\Models\DigitalProduct;
use App\Repositories\DigitalProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DigitalProductService
{
    public function __construct(protected DigitalProductRepository $digitalProductRepository) {}

    public function getAllDigitalProducts(): Collection
    {
        return $this->digitalProductRepository->getAllDigitalProducts();
    }

    public function getPaginatedDigitalProducts(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->digitalProductRepository->getPaginatedDigitalProducts($perPage, $filters);
    }

    public function getDigitalProductById(int $id): ?DigitalProduct
    {
        return $this->digitalProductRepository->getDigitalProductById($id);
    }
}

