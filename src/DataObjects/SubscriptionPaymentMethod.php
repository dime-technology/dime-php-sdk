<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The lightweight payment-method summary embedded in a {@see Subscription}
 * (the saved card or bank account the recurring charge runs against).
 */
final class SubscriptionPaymentMethod
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $type,
        public readonly ?string $lastFour,
        public readonly ?string $expiration,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            type: Arr::string($data, 'type'),
            lastFour: Arr::string($data, 'last_four'),
            expiration: Arr::string($data, 'expiration'),
        );
    }
}
