<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\DataObjects\PaymentMethod;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Payment method endpoints: listing, reading, creating, updating, and deleting
 * a customer's stored cards and bank accounts for a merchant.
 *
 * Every method takes the merchant `$sid` explicitly. The customer is identified
 * through the `$filters` (one of `phone`, `email`, or `uuid`) on read methods,
 * and through the `uuid` attribute on mutating methods.
 */
final class PaymentMethods extends AbstractResource
{
    /**
     * List a customer's payment methods. Identify the customer with exactly one
     * of `phone`, `email`, or `uuid`.
     *
     * @param  array{phone?: string, email?: string, uuid?: string}  $filters
     * @return CursorPage<PaymentMethod>
     */
    public function list(string $sid, array $filters): CursorPage
    {
        return $this->paginate(
            'GET',
            'payment-method/list',
            $this->envelope(['sid' => $sid], $filters),
            static fn (array $item): PaymentMethod => PaymentMethod::fromArray($item),
        );
    }

    /**
     * Show a single payment method. Identify the customer with one of `phone`,
     * `email`, or `uuid`.
     *
     * @param  array{phone?: string, email?: string, uuid?: string}  $filters
     */
    public function show(string $sid, int|string $paymentMethodId, array $filters): PaymentMethod
    {
        $raw = $this->transport->request('GET', 'payment-method/show', $this->envelope([
            'sid' => $sid,
            'payment_method_id' => $paymentMethodId,
        ], $filters));

        return PaymentMethod::fromArray($raw['data'] ?? []);
    }

    /**
     * Create a payment method for a customer. Pass card fields for `type` "cc"
     * or bank fields for `type` "ach".
     *
     * @param  array{
     *     uuid: string,
     *     type: string,
     *     cc_name_on_card?: string,
     *     cc_number?: int|string,
     *     cc_expiration_date?: string,
     *     cc_cvv?: int|string,
     *     cc_brand?: string,
     *     zip?: string,
     *     ach_bank_account_name?: string,
     *     ach_routing_number?: int|string,
     *     ach_account_number?: int|string,
     *     ach_ownership_type?: string,
     *     ach_account_type?: string,
     *     ach_bank_name?: string,
     *     addr1?: string,
     *     addr2?: string,
     *     city?: string,
     *     state?: string,
     *     default?: bool
     * }  $attributes
     */
    public function create(string $sid, array $attributes): PaymentMethod
    {
        $raw = $this->transport->request('POST', 'payment-method/create', $this->envelope(['sid' => $sid] + $attributes));

        return PaymentMethod::fromArray($raw['data'] ?? []);
    }

    /**
     * Update a payment method. Identify it with `payment_method_id` and the
     * owning customer with `uuid`.
     *
     * @param  array{
     *     payment_method_id: int|string,
     *     uuid: string,
     *     type: string,
     *     cc_name_on_card?: string,
     *     cc_number?: int|string,
     *     cc_expiration_date?: string,
     *     cc_cvv?: int|string,
     *     cc_brand?: string,
     *     zip?: string,
     *     ach_bank_account_name?: string,
     *     ach_routing_number?: int|string,
     *     ach_account_number?: int|string,
     *     ach_ownership_type?: string,
     *     ach_account_type?: string,
     *     ach_bank_name?: string,
     *     addr1?: string,
     *     addr2?: string,
     *     city?: string,
     *     state?: string,
     *     default?: bool
     * }  $attributes
     */
    public function update(string $sid, array $attributes): PaymentMethod
    {
        $raw = $this->transport->request('PATCH', 'payment-method/update', $this->envelope(['sid' => $sid] + $attributes));

        return PaymentMethod::fromArray($raw['data'] ?? []);
    }

    /**
     * Delete a payment method belonging to the customer identified by `$uuid`.
     */
    public function delete(string $sid, int|string $paymentMethodId, string $uuid): MessageResult
    {
        $raw = $this->transport->request('POST', 'payment-method/delete', $this->envelope([
            'sid' => $sid,
            'payment_method_id' => $paymentMethodId,
            'uuid' => $uuid,
        ]));

        return MessageResult::fromArray($raw['data'] ?? $raw);
    }
}
