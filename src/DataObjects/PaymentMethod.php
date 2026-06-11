<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A stored payment method, either a credit card (`type` "cc") or a bank account
 * (`type` "ach"). Card and ACH specific fields are populated according to the
 * method's `type`; the others are null.
 */
final class PaymentMethod
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $type,
        public readonly ?string $token,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $ccNameOnCard,
        public readonly ?string $ccLastFour,
        public readonly ?string $ccExpirationDate,
        public readonly ?string $ccBrand,
        public readonly ?string $achBankAccountName,
        public readonly ?string $achRoutingNumber,
        public readonly ?string $achAccountNumber,
        public readonly ?string $achOwnershipType,
        public readonly ?string $achAccountType,
        public readonly ?string $achBankName,
        public readonly ?string $status,
        public readonly ?string $statusDate,
        public readonly bool $enabled,
        public readonly bool $isDefault,
        public readonly ?string $addr1,
        public readonly ?string $addr2,
        public readonly ?string $addr3,
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
            id: Arr::int($data, 'id'),
            type: Arr::string($data, 'type'),
            token: Arr::string($data, 'token'),
            firstName: Arr::string($data, 'first_name'),
            lastName: Arr::string($data, 'last_name'),
            ccNameOnCard: Arr::string($data, 'cc_name_on_card'),
            ccLastFour: Arr::string($data, 'cc_last_four'),
            ccExpirationDate: Arr::string($data, 'cc_expiration_date'),
            ccBrand: Arr::string($data, 'cc_brand'),
            achBankAccountName: Arr::string($data, 'ach_bank_account_name'),
            achRoutingNumber: Arr::string($data, 'ach_routing_number'),
            achAccountNumber: Arr::string($data, 'ach_account_number'),
            achOwnershipType: Arr::string($data, 'ach_ownership_type'),
            achAccountType: Arr::string($data, 'ach_account_type'),
            achBankName: Arr::string($data, 'ach_bank_name'),
            status: Arr::string($data, 'status'),
            statusDate: Arr::string($data, 'status_date'),
            enabled: Arr::bool($data, 'enabled'),
            isDefault: Arr::bool($data, 'default'),
            addr1: Arr::string($data, 'addr1'),
            addr2: Arr::string($data, 'addr2'),
            addr3: Arr::string($data, 'addr3'),
            city: Arr::string($data, 'city'),
            state: Arr::string($data, 'state'),
            zip: Arr::string($data, 'zip'),
        );
    }
}
