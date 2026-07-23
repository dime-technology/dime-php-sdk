<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A single line item snapshotted onto a {@see SubscriptionPlan} or a
 * {@see Subscription}.
 *
 * Plan items carry only the name/description/quantity/unit price; subscription
 * items additionally expose the computed `amount`, so it is nullable here.
 * Monetary values are preserved as strings to avoid float rounding.
 */
final class SubscriptionItem
{
    public function __construct(
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
            name: Arr::string($data, 'name'),
            description: Arr::string($data, 'description'),
            quantity: Arr::string($data, 'quantity'),
            unitPrice: Arr::string($data, 'unit_price'),
            amount: Arr::string($data, 'amount'),
        );
    }
}
