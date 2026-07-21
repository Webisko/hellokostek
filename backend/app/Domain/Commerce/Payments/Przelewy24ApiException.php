<?php

namespace App\Domain\Commerce\Payments;

use RuntimeException;
use Throwable;

class Przelewy24ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $requestPayload
     * @param  array<string, mixed>|string|null  $responsePayload
     */
    public function __construct(
        string $message,
        public readonly array $requestPayload = [],
        public readonly array|string|null $responsePayload = null,
        public readonly ?int $statusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}