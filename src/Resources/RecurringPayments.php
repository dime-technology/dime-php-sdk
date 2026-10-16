<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\DataObjects\RecurringPayment;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Recurring-payment endpoints: scheduling, reading, editing, and controlling
 * the lifecycle (pause/cancel/activate/delete) of recurring payments.
 *
 * Every method takes the merchant `$sid` explicitly; remaining fields are
 * passed in `$attributes`/`$filters` and merged into the request's `data`
 * envelope. See each method's array shape for the accepted keys.
 */
final class RecurringPayments extends AbstractResource
{
    /**
     * List recurring payments for a merchant.
     *
     * @param  array{status?: string, customer_uuid?: string}  $filters
     * @return CursorPage<RecurringPayment>
     */
    public function list(string $sid, array $filters = []): CursorPage
    {
        return $this->paginate(
            'GET',
            'recurring-payment/list',
            $this->envelope(['sid' => $sid], $filters),
            static fn (array $item): RecurringPayment => RecurringPayment::fromArray($item),
        );
    }

    /**
     * Show a single recurring payment.
     */
    public function show(string $sid, int|string $recurringPaymentId): RecurringPayment
    {
        $raw = $this->transport->request('GET', 'recurring-payment/show', $this->envelope([
            'sid' => $sid,
            'recurring_payment_id' => $recurringPaymentId,
        ]));

        return RecurringPayment::fromArray($raw['data'] ?? []);
    }

    /**
     * Create a recurring payment schedule.
     *
     * `customer_covers_fee` true treats `amount` as what the merchant nets and
     * adds the processing fee on top of each charge. `item_id` pins the item
     * (designation) each run is credited to; omit it, or pass null, for the
     * merchant's default item.
     *
     * @param  array{
     *     name: string,
     *     amount: int|float|string,
     *     start_date: string,
     *     end_date?: string,
     *     recurrence_schedule: string,
     *     payment_method: int,
     *     customer_uuid: string,
     *     customer_covers_fee?: bool,
     *     item_id?: int|null,
     *     shipping_address?: array<string, mixed>
     * }  $attributes
     */
    public function create(string $sid, array $attributes): RecurringPayment
    {
        $raw = $this->transport->request('POST', 'recurring-payment/create', $this->envelope(['sid' => $sid] + $attributes));

        return RecurringPayment::fromArray($raw['data'] ?? []);
    }

    /**
     * Edit an existing recurring payment schedule.
     *
     * Omitted keys are left unchanged. `customer_covers_fee` true turns fee
     * coverage on (grossing up `amount`, or the current amount) and false
     * turns it off. `item_id` re-pins the item (designation) each run is
     * credited to; pass null to unpin it, so each run is credited to whatever
     * the merchant's default item is at the time. Unlike every other key, an
     * explicit null `item_id` is sent rather than dropped.
     *
     * @param  array{
     *     name?: string,
     *     amount?: int|float|string,
     *     start_date?: string,
     *     end_date?: string,
     *     recurrence_schedule?: string,
     *     payment_method?: int,
     *     customer_uuid?: string,
     *     customer_covers_fee?: bool,
     *     item_id?: int|null,
     *     shipping_address?: array<string, mixed>
     * }  $attributes
     */
    public function edit(string $sid, int|string $recurringPaymentId, array $attributes): RecurringPayment
    {
        $body = $this->envelope([
            'sid' => $sid,
            'recurring_payment_id' => $recurringPaymentId,
        ] + $attributes);

        if (array_key_exists('item_id', $attributes) && $attributes['item_id'] === null) {
            $body['data']['item_id'] = null;
        }

        $raw = $this->transport->request('PATCH', 'recurring-payment/edit', $body);

        return RecurringPayment::fromArray($raw['data'] ?? []);
    }

    /**
     * Pause a recurring payment, optionally until a given date. A null
     * `$pauseUntilDate` is pruned by the envelope (pauses indefinitely).
     */
    public function pause(string $sid, int|string $recurringPaymentId, ?string $pauseUntilDate = null): RecurringPayment
    {
        $raw = $this->transport->request('PATCH', 'recurring-payment/pause', $this->envelope([
            'sid' => $sid,
            'recurring_payment_id' => $recurringPaymentId,
            'pause_until_date' => $pauseUntilDate,
        ]));

        return RecurringPayment::fromArray($raw['data'] ?? []);
    }

    /**
     * Cancel a recurring payment.
     */
    public function cancel(string $sid, int|string $recurringPaymentId): RecurringPayment
    {
        $raw = $this->transport->request('PATCH', 'recurring-payment/cancel', $this->envelope([
            'sid' => $sid,
            'recurring_payment_id' => $recurringPaymentId,
        ]));

        return RecurringPayment::fromArray($raw['data'] ?? []);
    }

    /**
     * Reactivate a paused or cancelled recurring payment.
     */
    public function activate(string $sid, int|string $recurringPaymentId): RecurringPayment
    {
        $raw = $this->transport->request('PATCH', 'recurring-payment/activate', $this->envelope([
            'sid' => $sid,
            'recurring_payment_id' => $recurringPaymentId,
        ]));

        return RecurringPayment::fromArray($raw['data'] ?? []);
    }

    /**
     * Delete a recurring payment.
     */
    public function delete(string $sid, int|string $recurringPaymentId): MessageResult
    {
        $raw = $this->transport->request('POST', 'recurring-payment/delete', $this->envelope([
            'sid' => $sid,
            'recurring_payment_id' => $recurringPaymentId,
        ]));

        return MessageResult::fromArray($raw['data'] ?? $raw);
    }
}
