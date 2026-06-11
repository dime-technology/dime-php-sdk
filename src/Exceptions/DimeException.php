<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base exception for every error surfaced by the Dime SDK.
 */
class DimeException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $responseBody  The decoded JSON body returned by the API, if any.
     */
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly array $responseBody = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResponseBody(): array
    {
        return $this->responseBody;
    }
}
