<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The lightweight payment-method summary embedded in a {@see Subscription}
 * (the saved card or bank account the recurring charge runs against). Card and
 * bank fields are both nullable since only the set matching `type` ("cc" or
 * "ach") is populated.
 */
final class SubscriptionPaymentMethod
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $type,
        public readonly ?string $lastFour,
        public readonly ?string $expiration,
        public readonly ?string $bankName,
        public readonly ?string $accountType,
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
            bankName: Arr::string($data, 'bank_name'),
            accountType: Arr::string($data, 'account_type'),
        );
    }
}
