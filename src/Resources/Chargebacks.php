<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\Chargeback;
use DimePayments\Sdk\Exceptions\NotFoundException;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Chargeback endpoints: reading the chargebacks raised against a merchant.
 *
 * Chargeback data reaches Dime in a once-daily file from the processor, so
 * these reflect the most recent import rather than live dispute activity. Pair
 * them with the `chargeback_opened` / `chargeback_updated` /
 * `chargeback_resolved` webhooks: the webhooks say when something changed, and
 * these endpoints reconcile or backfill after an outage. To contest a
 * chargeback, upload evidence with {@see Documents::upload()}.
 *
 * Every method takes the merchant `$sid` explicitly.
 */
final class Chargebacks extends AbstractResource
{
    /**
     * List chargebacks for a merchant, oldest first. `start_date` and
     * `end_date` ("YYYY-mm-dd HH:ii:ss", UTC) must be given together.
     *
     * When nothing matches, the API answers 404, raised as a
     * {@see NotFoundException}, rather than returning an empty page.
     *
     * @param  array{start_date?: string, end_date?: string, representment_status?: string}  $filters
     * @return CursorPage<Chargeback>
     */
    public function list(string $sid, array $filters = []): CursorPage
    {
        return $this->paginate(
            'GET',
            'chargeback/list',
            $this->envelope(['sid' => $sid], $filters),
            static fn (array $item): Chargeback => Chargeback::fromArray($item),
        );
    }

    /**
     * Show a single chargeback, identified by its own `transaction_info_id` (as
     * returned by the list endpoint and the chargeback webhooks).
     */
    public function show(string $sid, int|string $transactionInfoId): Chargeback
    {
        $raw = $this->transport->request('GET', 'chargeback/show', $this->envelope([
            'sid' => $sid,
            'transaction_info_id' => (string) $transactionInfoId,
        ]));

        return Chargeback::fromArray($raw['data'] ?? []);
    }
}
