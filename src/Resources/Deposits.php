<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\Deposit;
use DimePayments\Sdk\DataObjects\DepositGroup;
use DimePayments\Sdk\DataObjects\DepositWithTransactions;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Deposit (sweep) endpoints: reading a merchant's deposits and the transactions
 * that make them up.
 *
 * Every method takes the merchant `$sid` explicitly; remaining fields are
 * passed in `$filters`/`$identifier` and merged into the request envelope.
 */
final class Deposits extends AbstractResource
{
    /**
     * List deposits for a merchant.
     *
     * @param  array{start_date?: string, end_date?: string}  $filters
     * @return CursorPage<Deposit>
     */
    public function list(string $sid, array $filters = []): CursorPage
    {
        return $this->paginate(
            'GET',
            'deposit/list',
            $this->envelope(['sid' => $sid], $filters),
            static fn (array $item): Deposit => Deposit::fromArray($item),
        );
    }

    /**
     * List deposits over a date range together with their transactions.
     *
     * @param  array{start_date: string, end_date: string}  $filters
     */
    public function listWithTransactions(string $sid, array $filters): DepositGroup
    {
        $raw = $this->transport->request('GET', 'deposit/list-with-trans', $this->envelope(['sid' => $sid], $filters));

        return DepositGroup::fromArray($raw['data'] ?? []);
    }

    /**
     * Show a single deposit and its transactions, identified either by
     * `transaction_info_id` or by `sweep_id`.
     *
     * @param  array{transaction_info_id?: int|string, sweep_id?: int|string}  $identifier
     */
    public function show(string $sid, array $identifier): DepositWithTransactions
    {
        $raw = $this->transport->request('GET', 'deposit/show', $this->envelope(['sid' => $sid] + $identifier));

        return DepositWithTransactions::fromArray($raw['data'] ?? []);
    }
}
