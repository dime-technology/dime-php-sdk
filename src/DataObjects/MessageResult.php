<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The simple `{ "message": "..." }` acknowledgement returned by mutating
 * endpoints such as refund, void, and the various delete operations.
 */
final class MessageResult
{
    public function __construct(public readonly ?string $message) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(message: Arr::string($data, 'message'));
    }
}
