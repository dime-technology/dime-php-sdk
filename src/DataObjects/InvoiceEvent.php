<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * An entry in an {@see Invoice}'s event/activity log (e.g. created, sent, paid).
 */
final class InvoiceEvent
{
    public function __construct(
        public readonly ?string $type,
        public readonly ?string $label,
        public readonly ?string $description,
        public readonly ?string $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: Arr::string($data, 'type'),
            label: Arr::string($data, 'label'),
            description: Arr::string($data, 'description'),
            createdAt: Arr::string($data, 'created_at'),
        );
    }
}
