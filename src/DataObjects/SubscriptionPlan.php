<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * A subscription plan — a recurring offering customers can subscribe to — as
 * returned by the list, show, create, edit, publish, archive, and unarchive
 * endpoints.
 *
 * `status` is one of "draft", "active", or "archived". `token`/`publicUrl`
 * expose the hosted subscribe page; `allowPublic` controls whether the plan is
 * listed in the public catalog. Monetary values are preserved as strings to
 * avoid float rounding.
 */
final class SubscriptionPlan
{
    /**
     * @param  array<int, SubscriptionItem>  $items
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $recurrenceSchedule,
        public readonly ?string $status,
        public readonly ?string $subtotal,
        public readonly ?string $total,
        public readonly ?string $token,
        public readonly ?string $publicUrl,
        public readonly bool $allowPublic,
        public readonly ?string $createdAt,
        public readonly array $items,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::int($data, 'id'),
            name: Arr::string($data, 'name'),
            description: Arr::string($data, 'description'),
            recurrenceSchedule: Arr::string($data, 'recurrence_schedule'),
            status: Arr::string($data, 'status'),
            subtotal: Arr::string($data, 'subtotal'),
            total: Arr::string($data, 'total'),
            token: Arr::string($data, 'token'),
            publicUrl: Arr::string($data, 'public_url'),
            allowPublic: Arr::bool($data, 'allow_public'),
            createdAt: Arr::string($data, 'created_at'),
            items: array_map(
                static fn (array $item): SubscriptionItem => SubscriptionItem::fromArray($item),
                array_values(Arr::arrayFrom($data, ['items'])),
            ),
        );
    }
}
