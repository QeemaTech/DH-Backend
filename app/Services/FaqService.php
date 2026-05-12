<?php

namespace App\Services;

use App\Models\Faq;
use App\Repositories\FaqRepository;
use Illuminate\Database\Eloquent\Collection;

class FaqService
{
    protected FaqRepository $faqRepository;

    public function __construct(FaqRepository $faqRepository)
    {
        $this->faqRepository = $faqRepository;
    }

    /**
     * Get all FAQs
     */
    public function getAllFaqs(): Collection
    {
        return $this->faqRepository->getAllFaqs();
    }

    /**
     * Get active FAQs for public display
     */
    public function getActiveFaqs(): Collection
    {
        return $this->faqRepository->getActiveFaqs();
    }

    /**
     * Get a FAQ by ID
     */
    public function getFaqById(int $id): ?Faq
    {
        return $this->faqRepository->getFaqById($id);
    }

    /**
     * Create a new FAQ
     */
    public function createFaq(array $faqData): Faq
    {
        return $this->faqRepository->createFaq($faqData);
    }

    /**
     * Update a FAQ
     */
    public function updateFaq(int $id, array $faqData): ?Faq
    {
        return $this->faqRepository->updateFaq($id, $faqData);
    }

    /**
     * Delete a FAQ
     */
    public function deleteFaq(int $id): bool
    {
        return $this->faqRepository->deleteFaq($id);
    }
}
