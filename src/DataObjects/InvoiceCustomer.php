<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The customer snapshot embedded in a full {@see Invoice} or
 * {@see RecurringInvoice} (id, name, email).
 */
final class InvoiceCustomer
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $email,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            name: Arr::string($data, 'name'),
            email: Arr::string($data, 'email'),
        );
    }
}
