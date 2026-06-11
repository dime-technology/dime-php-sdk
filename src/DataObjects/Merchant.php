<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A merchant account as returned by the API.
 */
final class Merchant
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $sid,
        public readonly ?string $mcc,
        public readonly ?string $slug,
        public readonly ?string $pubApiKey,
        public readonly ?string $processorMid,
        public readonly bool $active,
        public readonly ?string $activeAt,
        public readonly bool $gPay,
        public readonly bool $aPay,
        public readonly bool $pciCompliance,
        public readonly ?string $website,
        public readonly ?string $addr1,
        public readonly ?string $addr2,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly ?string $zip,
        public readonly ?string $phone,
        public readonly ?string $primaryPhone,
        public readonly ?string $primaryEmail,
        public readonly ?string $primaryName,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Arr::string($data, 'name'),
            sid: Arr::string($data, 'sid'),
            mcc: Arr::string($data, 'mcc'),
            slug: Arr::string($data, 'slug'),
            pubApiKey: Arr::string($data, 'pub_api_key'),
            processorMid: Arr::string($data, 'processor_mid'),
            active: Arr::bool($data, 'active'),
            activeAt: Arr::string($data, 'active_at'),
            gPay: Arr::bool($data, 'g_pay'),
            aPay: Arr::bool($data, 'a_pay'),
            pciCompliance: Arr::bool($data, 'pci_compliance'),
            website: Arr::string($data, 'website'),
            addr1: Arr::string($data, 'addr1'),
            addr2: Arr::string($data, 'addr2'),
            city: Arr::string($data, 'city'),
            state: Arr::string($data, 'state'),
            zip: Arr::string($data, 'zip'),
            phone: Arr::string($data, 'phone'),
            primaryPhone: Arr::string($data, 'primary_phone'),
            primaryEmail: Arr::string($data, 'primary_email'),
            primaryName: Arr::string($data, 'primary_name'),
        );
    }
}
