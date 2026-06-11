<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * Billing or shipping address embedded in a {@see Transaction}. Shipping
 * addresses do not carry a name, so those fields are nullable.
 */
final class TransactionAddress
{
    public function __construct(
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $addr1,
        public readonly ?string $addr2,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly ?string $zip,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: Arr::string($data, 'first_name'),
            lastName: Arr::string($data, 'last_name'),
            addr1: Arr::string($data, 'addr1'),
            addr2: Arr::string($data, 'addr2'),
            city: Arr::string($data, 'city'),
            state: Arr::string($data, 'state'),
            zip: Arr::string($data, 'zip'),
        );
    }
}
