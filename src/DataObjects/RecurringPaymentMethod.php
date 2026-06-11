<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The payment-method summary embedded in a {@see RecurringPayment}. Card and
 * bank fields are both nullable since only the relevant set is populated.
 */
final class RecurringPaymentMethod
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $type,
        public readonly ?string $lastFour,
        public readonly ?string $expiration,
        public readonly ?string $zip,
        public readonly ?string $nameOnCard,
        public readonly ?string $bankAccountName,
        public readonly ?string $routingNumber,
        public readonly ?string $accountNumber,
        public readonly ?string $ownershipType,
        public readonly ?string $accountType,
        public readonly ?string $bankName,
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
            zip: Arr::string($data, 'zip'),
            nameOnCard: Arr::string($data, 'name_on_card'),
            bankAccountName: Arr::string($data, 'bank_account_name'),
            routingNumber: Arr::string($data, 'routing_number'),
            accountNumber: Arr::string($data, 'account_number'),
            ownershipType: Arr::string($data, 'ownership_type'),
            accountType: Arr::string($data, 'account_type'),
            bankName: Arr::string($data, 'bank_name'),
        );
    }
}
