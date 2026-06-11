<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\Address;
use DimePayments\Sdk\DataObjects\MessageResult;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Address endpoints: listing, reading, creating, updating, and deleting the
 * addresses associated with a merchant's customer.
 *
 * Every method identifies the merchant by `sid` and the customer by `uuid`,
 * both passed in the request's `data` envelope alongside the address fields.
 */
final class Addresses extends AbstractResource
{
    /**
     * List a customer's addresses.
     *
     * @return CursorPage<Address>
     */
    public function list(string $sid, string $uuid): CursorPage
    {
        return $this->paginate(
            'GET',
            'address/list',
            $this->envelope(['sid' => $sid, 'uuid' => $uuid]),
            static fn (array $item): Address => Address::fromArray($item),
        );
    }

    /**
     * Show a single address.
     */
    public function show(string $sid, string $uuid, int|string $addressId): Address
    {
        $raw = $this->transport->request('GET', 'address/show', $this->envelope([
            'sid' => $sid,
            'uuid' => $uuid,
            'address_id' => $addressId,
        ]));

        return Address::fromArray($raw['data'] ?? []);
    }

    /**
     * Create an address for a customer.
     *
     * @param  array{
     *     recipient: string,
     *     line_one: string,
     *     line_two?: string,
     *     line_three?: string,
     *     city: string,
     *     state: string,
     *     zip: int|string
     * }  $attributes
     */
    public function create(string $sid, string $uuid, array $attributes): Address
    {
        $raw = $this->transport->request('POST', 'address/create', $this->envelope([
            'sid' => $sid,
            'uuid' => $uuid,
        ] + $attributes));

        return Address::fromArray($raw['data'] ?? []);
    }

    /**
     * Update an address.
     *
     * @param  array{
     *     recipient?: string,
     *     line_one?: string,
     *     line_two?: string,
     *     line_three?: string,
     *     city?: string,
     *     state?: string,
     *     zip?: int|string
     * }  $attributes
     */
    public function update(string $sid, string $uuid, int|string $addressId, array $attributes): Address
    {
        $raw = $this->transport->request('PATCH', 'address/update', $this->envelope([
            'sid' => $sid,
            'uuid' => $uuid,
            'address_id' => $addressId,
        ] + $attributes));

        return Address::fromArray($raw['data'] ?? []);
    }

    /**
     * Delete an address.
     */
    public function delete(string $sid, string $uuid, int|string $addressId): MessageResult
    {
        $raw = $this->transport->request('POST', 'address/delete', $this->envelope([
            'sid' => $sid,
            'uuid' => $uuid,
            'address_id' => $addressId,
        ]));

        return MessageResult::fromArray($raw['data'] ?? $raw);
    }
}
