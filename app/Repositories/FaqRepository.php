<?php

namespace App\Repositories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;

class FaqRepository
{
    protected Faq $faq;

    public function __construct(Faq $faq)
    {
        $this->faq = $faq;
    }

    /**
     * Get all FAQs
     */
    public function getAllFaqs(): Collection
    {
        return $this->faq->orderBy('order')->get();
    }

    /**
     * Get active FAQs ordered by order
     */
    public function getActiveFaqs(): Collection
    {
        return $this->faq->active()->orderBy('order')->get();
    }

    /**
     * Get a FAQ by ID
     */
    public function getFaqById(int $id): ?Faq
    {
        return $this->faq->find($id);
    }

    /**
     * Create a new FAQ
     */
    public function createFaq(array $faqData): Faq
    {
        return $this->faq->create($faqData);
    }

    /**
     * Update a FAQ
     */
    public function updateFaq(int $id, array $faqData): ?Faq
    {
        $faq = $this->faq->find($id);
        if ($faq) {
            $faq->update($faqData);

            return $faq->fresh();
        }

        return null;
    }

    /**
     * Delete a FAQ
     */
    public function deleteFaq(int $id): bool
    {
        $faq = $this->faq->find($id);

        return $faq ? $faq->delete() : false;
    }
}
