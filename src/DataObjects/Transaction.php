<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A transaction as returned by the API (the "generic transaction" shape shared
 * by list, show, charge, and deposit-detail responses).
 *
 * Amounts are returned by the API as decimal strings (e.g. "25.0000") and are
 * preserved as strings here to avoid float rounding.
 */
final class Transaction
{
    public function __construct(
        public readonly ?string $transactionType,
        public readonly ?string $transactionStatus,
        public readonly ?string $transactionStatusDescription,
        public readonly ?string $transactionNumber,
        public readonly ?string $transactionDate,
        public readonly ?string $fundDate,
        public readonly ?string $settleDate,
        public readonly ?string $amount,
        public readonly ?string $description,
        public readonly ?string $statusCode,
        public readonly ?string $statusText,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $customerUuid,
        public readonly ?string $multiUseToken,
        public readonly bool $pending,
        public readonly ?string $transactionInfoId,
        public readonly ?string $parentTransactionInfoId,
        public readonly TransactionAddress $billingAddress,
        public readonly TransactionAddress $shippingAddress,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionType: Arr::string($data, 'transaction_type'),
            transactionStatus: Arr::string($data, 'transaction_status'),
            transactionStatusDescription: Arr::string($data, 'transaction_status_description'),
            transactionNumber: Arr::string($data, 'transaction_number'),
            transactionDate: Arr::string($data, 'transaction_date'),
            fundDate: Arr::string($data, 'fund_date'),
            settleDate: Arr::string($data, 'settle_date'),
            amount: Arr::string($data, 'amount'),
            description: Arr::string($data, 'description'),
            statusCode: Arr::string($data, 'status_code'),
            statusText: Arr::string($data, 'status_text'),
            email: Arr::string($data, 'email'),
            phone: Arr::string($data, 'phone'),
            customerUuid: Arr::string($data, 'customer_uuid'),
            multiUseToken: Arr::string($data, 'multi_use_token'),
            pending: Arr::bool($data, 'pending'),
            transactionInfoId: Arr::string($data, 'transaction_info_id'),
            parentTransactionInfoId: Arr::string($data, 'parent_transaction_info_id'),
            billingAddress: TransactionAddress::fromArray(Arr::arrayFrom($data, ['billing_address'])),
            // The API returns shipping under the camelCase key `shippingAddress`;
            // accept the snake_case form too in case that is ever corrected.
            shippingAddress: TransactionAddress::fromArray(Arr::arrayFrom($data, ['shippingAddress', 'shipping_address'])),
        );
    }
}
