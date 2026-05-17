<?php

namespace App\Exceptions;

use Exception;

class DigitalProductPurchaseLimitException extends Exception
{
    public function __construct(
        string $message,
        protected array $responseData = [],
        protected int $statusCode = 422
    ) {
        parent::__construct($message);
    }

    public function responseData(): array
    {
        return $this->responseData;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
