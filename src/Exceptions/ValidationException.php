<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Exceptions;

use Throwable;

/**
 * Thrown when the API rejects a request with field-level validation errors
 * (HTTP 400/422 with an `errors` payload).
 */
class ValidationException extends DimeException
{
    /**
     * @param  array<string, array<int, string>>  $errors  Map of field name to its list of messages.
     * @param  array<string, mixed>  $responseBody
     */
    public function __construct(
        string $message,
        public readonly array $errors = [],
        ?int $statusCode = null,
        array $responseBody = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $responseBody, $previous);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * The first validation message, useful for quick display.
     */
    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            foreach ($messages as $message) {
                return $message;
            }
        }

        return null;
    }
}
