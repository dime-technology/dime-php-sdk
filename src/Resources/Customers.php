<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\Customer;
use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Customer endpoints: listing, reading, creating, updating, and deleting the
 * customers belonging to a merchant.
 *
 * Every method takes the merchant `$sid` explicitly; remaining fields are
 * passed in `$attributes` (merged into the request's `data` envelope) or in
 * `$filters` (merged into the `filters` envelope). See each method's array
 * shape for the accepted keys.
 */
final class Customers extends AbstractResource
{
    /**
     * List customers for a merchant.
     *
     * @param  array{start_date?: string, end_date?: string, phone?: string, email?: string}  $filters
     * @return CursorPage<Customer>
     */
    public function list(string $sid, array $filters = []): CursorPage
    {
        return $this->paginate(
            'GET',
            'customer/list',
            $this->envelope(['sid' => $sid], $filters),
            static fn (array $item): Customer => Customer::fromArray($item),
        );
    }

    /**
     * Show a single customer, located by exactly one of `phone`, `email`, or
     * `uuid`.
     *
     * @param  array{phone?: string, email?: string, uuid?: string}  $filters
     */
    public function show(string $sid, array $filters): Customer
    {
        $raw = $this->transport->request('GET', 'customer/show', $this->envelope(['sid' => $sid], $filters));

        return Customer::fromArray($raw['data'] ?? []);
    }

    /**
     * Create a customer. One of `phone` or `email` is required.
     *
     * @param  array{
     *     first_name: string,
     *     last_name: string,
     *     company_name?: string,
     *     phone?: string,
     *     email?: string,
     *     addr1?: string,
     *     addr2?: string,
     *     addr3?: string,
     *     city?: string,
     *     state?: string,
     *     zip?: string,
     *     country?: string
     * }  $attributes
     */
    public function create(string $sid, array $attributes): Customer
    {
        $raw = $this->transport->request('POST', 'customer/create', $this->envelope(['sid' => $sid] + $attributes));

        return Customer::fromArray($raw['data'] ?? []);
    }

    /**
     * Update a customer located by exactly one of `phone`, `email`, or `uuid`.
     * Note `phone` and `email` are not updatable and so are absent from the
     * attribute shape.
     *
     * @param  array{phone?: string, email?: string, uuid?: string}  $filters
     * @param  array{
     *     first_name?: string,
     *     last_name?: string,
     *     company_name?: string,
     *     addr1?: string,
     *     addr2?: string,
     *     addr3?: string,
     *     city?: string,
     *     state?: string,
     *     zip?: string,
     *     country?: string
     * }  $attributes
     */
    public function update(string $sid, array $filters, array $attributes): Customer
    {
        $raw = $this->transport->request('PATCH', 'customer/update', $this->envelope(['sid' => $sid] + $attributes, $filters));

        return Customer::fromArray($raw['data'] ?? []);
    }

    /**
     * Delete a customer located by exactly one of `phone`, `email`, or `uuid`.
     *
     * @param  array{phone?: string, email?: string, uuid?: string}  $filters
     */
    public function delete(string $sid, array $filters): MessageResult
    {
        $raw = $this->transport->request('POST', 'customer/delete', $this->envelope(['sid' => $sid], $filters));

        return MessageResult::fromArray($raw['data'] ?? $raw);
    }
}
