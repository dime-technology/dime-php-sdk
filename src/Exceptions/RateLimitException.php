<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Exceptions;

use Throwable;

/**
 * Thrown for HTTP 429 responses when the request budget is exhausted and
 * retries (if enabled) have been exhausted too.
 */
class RateLimitException extends DimeException
{
    /**
     * @param  array<string, mixed>  $responseBody
     */
    public function __construct(
        string $message,
        public readonly ?int $retryAfter = null,
        ?int $statusCode = 429,
        array $responseBody = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $responseBody, $previous);
    }

    /**
     * Seconds to wait before retrying, taken from the Retry-After header.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
