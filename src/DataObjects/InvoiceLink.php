<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * The public pay link (and token) for an invoice, returned by the get-link
 * endpoint.
 */
final class InvoiceLink
{
    public function __construct(
        public readonly ?string $publicUrl,
        public readonly ?string $token,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            publicUrl: Arr::string($data, 'public_url'),
            token: Arr::string($data, 'token'),
        );
    }
}
