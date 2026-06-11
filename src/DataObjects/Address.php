<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A customer address as returned by the API.
 *
 * The identifier is returned under `address_id` on the show endpoint and under
 * `id` on list items, so both keys are read.
 */
final class Address
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $recipient,
        public readonly ?string $lineOne,
        public readonly ?string $lineTwo,
        public readonly ?string $lineThree,
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
            id: Arr::int($data, 'address_id') ?? Arr::int($data, 'id'),
            recipient: Arr::string($data, 'recipient'),
            lineOne: Arr::string($data, 'line_one'),
            lineTwo: Arr::string($data, 'line_two'),
            lineThree: Arr::string($data, 'line_three'),
            city: Arr::string($data, 'city'),
            state: Arr::string($data, 'state'),
            zip: Arr::string($data, 'zip'),
        );
    }
}
