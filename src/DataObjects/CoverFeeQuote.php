<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The processing fee a cover-fee invoice adds on top of what the customer pays,
 * quoted for both payment methods.
 *
 * Present on an {@see Invoice} only when `cover_fee_required` is true. The fee is
 * NOT a line item and is NOT part of the invoice's `total`: the merchant is owed
 * `total`, and the customer is charged `total` plus this fee. Card and ACH rates
 * differ, so the amount depends on how the customer chooses to pay — `ccTotal` is
 * the higher of the two and what the invoice and its emails lead with.
 *
 * `basis` names what the quote was computed against — currently always `balance`,
 * the amount still outstanding. Paying a partial amount instead re-quotes the fee
 * against that amount, so treat these figures as a quote for settling in full
 * today rather than a fixed charge.
 *
 * Monetary values are preserved as strings to avoid float rounding.
 */
final class CoverFeeQuote
{
    public function __construct(
        public readonly ?string $basis,
        public readonly ?string $base,
        public readonly ?string $ccFee,
        public readonly ?string $ccTotal,
        public readonly ?string $achFee,
        public readonly ?string $achTotal,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $cc = Arr::arrayFrom($data, ['cc']);
        $ach = Arr::arrayFrom($data, ['ach']);

        return new self(
            basis: Arr::string($data, 'basis'),
            base: Arr::string($data, 'base'),
            ccFee: Arr::string($cc, 'fee'),
            ccTotal: Arr::string($cc, 'total'),
            achFee: Arr::string($ach, 'fee'),
            achTotal: Arr::string($ach, 'total'),
        );
    }
}
