<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A customer record as returned by the customer endpoints (list, show, create,
 * update).
 *
 * The `uuid` uniquely identifies the customer for a merchant and is the
 * preferred way to reference one in subsequent requests.
 */
final class Customer
{
    public function __construct(
        public readonly ?string $uuid,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $addr1,
        public readonly ?string $addr2,
        public readonly ?string $addr3,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly ?string $zip,
        public readonly ?string $country,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            uuid: Arr::string($data, 'uuid'),
            firstName: Arr::string($data, 'first_name'),
            lastName: Arr::string($data, 'last_name'),
            phone: Arr::string($data, 'phone'),
            email: Arr::string($data, 'email'),
            addr1: Arr::string($data, 'addr1'),
            addr2: Arr::string($data, 'addr2'),
            addr3: Arr::string($data, 'addr3'),
            city: Arr::string($data, 'city'),
            state: Arr::string($data, 'state'),
            zip: Arr::string($data, 'zip'),
            country: Arr::string($data, 'country'),
        );
    }
}
