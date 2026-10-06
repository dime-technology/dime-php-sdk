<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\DataObjects\SubscribeResult;
use DimePayments\Sdk\DataObjects\SubscriptionPlan;
use DimePayments\Sdk\Exceptions\ApiException;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Subscription-plan endpoints: creating, reading, and editing plans (recurring
 * offerings customers subscribe to), managing their lifecycle
 * (publish/archive/unarchive), and subscribing a customer to a plan.
 *
 * Every method takes the merchant `$sid` explicitly; remaining fields are
 * passed in `$attributes`/`$filters` and merged into the request's `data`
 * envelope. See each method's array shape for the accepted keys.
 */
final class SubscriptionPlans extends AbstractResource
{
    /**
     * List subscription plans for a merchant, newest first, optionally filtered
     * by status.
     *
     * Unlike most list endpoints, this one reads `status` from the `data`
     * envelope rather than `filters`; the SDK sends it where the API expects it.
     * An unrecognised status is ignored by the API and returns every plan.
     *
     * @param  array{status?: 'draft'|'active'|'archived'}  $filters
     * @return CursorPage<SubscriptionPlan>
     */
    public function list(string $sid, array $filters = []): CursorPage
    {
        return $this->paginate(
            'GET',
            'subscription-plan/list',
            $this->envelope(['sid' => $sid] + $filters),
            static fn (array $item): SubscriptionPlan => SubscriptionPlan::fromArray($item),
        );
    }

    /**
     * Show a single subscription plan with its line items.
     */
    public function show(string $sid, int|string $subscriptionPlanId): SubscriptionPlan
    {
        $raw = $this->transport->request('GET', 'subscription-plan/show', $this->envelope([
            'sid' => $sid,
            'subscription_plan_id' => $subscriptionPlanId,
        ]));

        return SubscriptionPlan::fromArray($raw['data'] ?? []);
    }

    /**
     * Create a subscription plan. The plan is created as a draft; publish it
     * (or use the merchant UI) before customers can subscribe. Every line must
     * reference a Merchant `item_id`; the line name and unit price are
     * snapshotted onto the plan.
     *
     * @param  array{
     *     name: string,
     *     description?: string,
     *     recurrence_schedule: 'Weekly'|'Biweekly'|'FirstFifteenth'|'Monthly'|'Yearly',
     *     allow_public?: bool,
     *     lines: array<int, array{item_id: int|string, name: string, description?: string, quantity: int|float|string, unit_price: int|float|string}>
     * }  $attributes
     */
    public function create(string $sid, array $attributes): SubscriptionPlan
    {
        $raw = $this->transport->request('POST', 'subscription-plan/create', $this->envelope(['sid' => $sid] + $attributes));

        return SubscriptionPlan::fromArray($raw['data'] ?? []);
    }

    /**
     * Edit a subscription plan. Replaces the plan's fields and line items
     * wholesale; existing subscribers keep their own snapshot and are unaffected.
     *
     * Because the replacement is wholesale, send every field you want to keep:
     * an omitted `description` is cleared and an omitted `allow_public` turns
     * the plan's catalog listing off.
     *
     * @param  array{
     *     name: string,
     *     description?: string,
     *     recurrence_schedule: 'Weekly'|'Biweekly'|'FirstFifteenth'|'Monthly'|'Yearly',
     *     allow_public?: bool,
     *     lines: array<int, array{item_id: int|string, name: string, description?: string, quantity: int|float|string, unit_price: int|float|string}>
     * }  $attributes
     */
    public function edit(string $sid, int|string $subscriptionPlanId, array $attributes): SubscriptionPlan
    {
        $raw = $this->transport->request('PATCH', 'subscription-plan/edit', $this->envelope([
            'sid' => $sid,
            'subscription_plan_id' => $subscriptionPlanId,
        ] + $attributes));

        return SubscriptionPlan::fromArray($raw['data'] ?? []);
    }

    /**
     * Delete a subscription plan. Only plans with no subscribers can be deleted;
     * otherwise archive it.
     */
    public function delete(string $sid, int|string $subscriptionPlanId): MessageResult
    {
        $raw = $this->transport->request('POST', 'subscription-plan/delete', $this->envelope([
            'sid' => $sid,
            'subscription_plan_id' => $subscriptionPlanId,
        ]));

        return MessageResult::fromArray($raw['data'] ?? $raw);
    }

    /**
     * Publish a draft plan, moving it to active so customers can subscribe. The
     * plan must be a draft and carry at least one line item.
     */
    public function publish(string $sid, int|string $subscriptionPlanId): SubscriptionPlan
    {
        $raw = $this->transport->request('PATCH', 'subscription-plan/publish', $this->envelope([
            'sid' => $sid,
            'subscription_plan_id' => $subscriptionPlanId,
        ]));

        return SubscriptionPlan::fromArray($raw['data'] ?? []);
    }

    /**
     * Archive a plan. Stops new subscriptions and hides it from the catalog;
     * existing subscribers keep their subscription.
     */
    public function archive(string $sid, int|string $subscriptionPlanId): SubscriptionPlan
    {
        $raw = $this->transport->request('PATCH', 'subscription-plan/archive', $this->envelope([
            'sid' => $sid,
            'subscription_plan_id' => $subscriptionPlanId,
        ]));

        return SubscriptionPlan::fromArray($raw['data'] ?? []);
    }

    /**
     * Reactivate an archived plan, moving it back to draft so it can be reviewed
     * and re-published. `allow_public` stays off until the merchant opts back
     * into the catalog.
     */
    public function unarchive(string $sid, int|string $subscriptionPlanId): SubscriptionPlan
    {
        $raw = $this->transport->request('PATCH', 'subscription-plan/unarchive', $this->envelope([
            'sid' => $sid,
            'subscription_plan_id' => $subscriptionPlanId,
        ]));

        return SubscriptionPlan::fromArray($raw['data'] ?? []);
    }

    /**
     * Subscribe a customer to a plan. Charges the first payment against the
     * customer's saved payment method and enrolls them; the plan must be active
     * and the payment method must belong to the customer.
     *
     * A declined first charge enrolls no one and throws an {@see ApiException}
     * (HTTP 422) whose response body carries the processor's `message` and the
     * `transaction_number`.
     *
     * @param  array{customer_uuid: string, payment_method: int|string}  $attributes
     */
    public function subscribe(string $sid, int|string $subscriptionPlanId, array $attributes): SubscribeResult
    {
        $raw = $this->transport->request('POST', 'subscription-plan/subscribe', $this->envelope([
            'sid' => $sid,
            'subscription_plan_id' => $subscriptionPlanId,
        ] + $attributes));

        return SubscribeResult::fromArray($raw['data'] ?? []);
    }
}
