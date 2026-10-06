<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Resources\SubscriptionPlans;
use DimePayments\Sdk\Support\Arr;

/**
 * The result of subscribing a customer to a plan
 * ({@see SubscriptionPlans::subscribe()}): the id of
 * the newly created subscription plus a summary of the first charge.
 *
 * The `amount` charged is preserved as a string to avoid float rounding.
 */
final class SubscribeResult
{
    public function __construct(
        public readonly ?int $subscriptionId,
        public readonly ?string $status,
        public readonly ?string $nextRunDate,
        public readonly ?string $transactionNumber,
        public readonly ?string $amount,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            subscriptionId: Arr::int($data, 'subscription_id'),
            status: Arr::string($data, 'status'),
            nextRunDate: Arr::string($data, 'next_run_date'),
            transactionNumber: Arr::string($data, 'transaction_number'),
            amount: Arr::string($data, 'amount'),
        );
    }
}
