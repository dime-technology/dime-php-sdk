<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A single line item on an {@see Invoice} or {@see RecurringInvoice}.
 *
 * `itemId` references the Merchant item (fund/designation) the line was sourced
 * from. Monetary values are preserved as strings to avoid float rounding.
 */
final class InvoiceLineItem
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $itemId,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $quantity,
        public readonly ?string $unitPrice,
        public readonly ?string $amount,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            itemId: Arr::int($data, 'item_id'),
            name: Arr::string($data, 'name'),
            description: Arr::string($data, 'description'),
            quantity: Arr::string($data, 'quantity'),
            unitPrice: Arr::string($data, 'unit_price'),
            amount: Arr::string($data, 'amount'),
        );
    }
}
