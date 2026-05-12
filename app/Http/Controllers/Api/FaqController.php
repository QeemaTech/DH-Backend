<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResource;
use App\Services\FaqService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FaqController extends Controller
{
    public function __construct(protected FaqService $service) {}

    public function index(): AnonymousResourceCollection
    {
        $faqs = $this->service->getActiveFaqs();

        return FaqResource::collection($faqs);
    }
}
