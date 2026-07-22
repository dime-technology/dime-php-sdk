<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A Merchant item (fund/designation) usable as an invoice line item, as
 * returned by the list-items and create-item endpoints.
 *
 * The list endpoint omits `tax_deductible`; it therefore defaults to false
 * there. The default unit price is preserved as a string to avoid float
 * rounding.
 */
final class InvoiceItem
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $price,
        public readonly bool $taxDeductible,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            name: Arr::string($data, 'name'),
            description: Arr::string($data, 'description'),
            price: Arr::string($data, 'price'),
            taxDeductible: Arr::bool($data, 'tax_deductible'),
        );
    }
}
