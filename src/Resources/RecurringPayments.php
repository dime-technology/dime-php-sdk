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
     * @param  array{
     *     name: string,
     *     amount: int|float|string,
     *     start_date: string,
     *     end_date?: string,
     *     recurrence_schedule: string,
     *     payment_method: int,
     *     customer_uuid: string,
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
     * @param  array{
     *     name?: string,
     *     amount?: int|float|string,
     *     start_date?: string,
     *     end_date?: string,
     *     recurrence_schedule?: string,
     *     payment_method?: int,
     *     customer_uuid?: string,
     *     shipping_address?: array<string, mixed>
     * }  $attributes
     */
    public function edit(string $sid, int|string $recurringPaymentId, array $attributes): RecurringPayment
    {
        $raw = $this->transport->request('PATCH', 'recurring-payment/edit', $this->envelope([
            'sid' => $sid,
            'recurring_payment_id' => $recurringPaymentId,
        ] + $attributes));

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
