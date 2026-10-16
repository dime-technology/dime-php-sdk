<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The item (designation) a {@see RecurringPayment} is pinned to, which each
 * run is credited to.
 *
 * `name` can be null while `id` is set, when the API does not disclose the
 * item's name.
 */
final class RecurringPaymentItem
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            name: Arr::string($data, 'name'),
        );
    }
}
