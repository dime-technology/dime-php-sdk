<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A chargeback raised against a merchant, as returned by the chargeback list
 * and show endpoints (the same shape the chargeback webhooks carry).
 *
 * Chargeback data arrives in a once-daily file from the processor, so this
 * reflects the most recent import rather than live dispute activity.
 * `representmentStatus` and `result` are free text from the processor (e.g.
 * "New", "accepting chargeback", "Resolved"); read `resolved` to know whether
 * the dispute has reached a terminal state. `transactionInfoId` identifies the
 * chargeback itself and is what document uploads reference as evidence;
 * `parentTransactionInfoId` is the disputed payment.
 *
 * Amounts are preserved as strings to avoid float rounding.
 */
final class Chargeback
{
    public function __construct(
        public readonly ?string $transactionInfoId,
        public readonly ?string $parentTransactionInfoId,
        public readonly ?string $gatewayTransactionId,
        public readonly ?string $transactionNumber,
        public readonly ?string $invoiceNumber,
        public readonly ?string $chargebackDate,
        public readonly ?string $merchantChargebackDate,
        public readonly ?string $transactionAmount,
        public readonly ?string $chargebackAmount,
        public readonly ?string $cardBrand,
        public readonly ?string $ccLastFour,
        public readonly ?string $payeeName,
        public readonly ?int $daysToRepresent,
        public readonly ?string $representmentDate,
        public readonly ?string $merchantRepresentmentDate,
        public readonly ?string $representmentStatus,
        public readonly ?string $result,
        public readonly ?string $chargebackCode,
        public readonly ?string $chargebackResponseCode,
        public readonly bool $resolved,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionInfoId: Arr::string($data, 'transaction_info_id'),
            parentTransactionInfoId: Arr::string($data, 'parent_transaction_info_id'),
            gatewayTransactionId: Arr::string($data, 'gateway_transaction_id'),
            transactionNumber: Arr::string($data, 'transaction_number'),
            invoiceNumber: Arr::string($data, 'invoice_number'),
            chargebackDate: Arr::string($data, 'chargeback_date'),
            merchantChargebackDate: Arr::string($data, 'merchant_chargeback_date'),
            transactionAmount: Arr::string($data, 'transaction_amount'),
            chargebackAmount: Arr::string($data, 'chargeback_amount'),
            cardBrand: Arr::string($data, 'card_brand'),
            ccLastFour: Arr::string($data, 'cc_last_four'),
            payeeName: Arr::string($data, 'payee_name'),
            daysToRepresent: Arr::int($data, 'days_to_represent'),
            representmentDate: Arr::string($data, 'representment_date'),
            merchantRepresentmentDate: Arr::string($data, 'merchant_representment_date'),
            representmentStatus: Arr::string($data, 'representment_status'),
            result: Arr::string($data, 'result'),
            chargebackCode: Arr::string($data, 'chargeback_code'),
            chargebackResponseCode: Arr::string($data, 'chargeback_response_code'),
            resolved: Arr::bool($data, 'resolved'),
        );
    }
}
