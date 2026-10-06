<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\ApplicationStatus;
use DimePayments\Sdk\DataObjects\FormLink;
use DimePayments\Sdk\DataObjects\Merchant;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Merchant endpoints: listing, reading, creating, and updating merchant
 * accounts, plus fetching the hosted onboarding form link and following the
 * merchant's onboarding status.
 *
 * The merchant `sid` is generated server-side on create; list and create take
 * no sid, while show, update, get-form-link, and application-status identify
 * the merchant by `sid` in the request's `data` envelope.
 */
final class Merchants extends AbstractResource
{
    /**
     * List merchants.
     *
     * @param  array{start_date?: string, end_date?: string}  $filters
     * @return CursorPage<Merchant>
     */
    public function list(array $filters = []): CursorPage
    {
        return $this->paginate(
            'GET',
            'merchant/list',
            $this->envelope([], $filters),
            static fn (array $item): Merchant => Merchant::fromArray($item),
        );
    }

    /**
     * Show a single merchant.
     */
    public function show(string $sid): Merchant
    {
        $raw = $this->transport->request('GET', 'merchant/show', $this->envelope(['sid' => $sid]));

        return Merchant::fromArray($raw['data'] ?? []);
    }

    /**
     * Create a merchant. The `sid` is generated server-side and returned on the
     * resulting merchant.
     *
     * @param  array{
     *     name: string,
     *     slug: string,
     *     mcc: int|string,
     *     processor_config_one: mixed,
     *     website: string,
     *     addr1: string,
     *     addr2?: string,
     *     city: string,
     *     state: string,
     *     zip: int|string,
     *     phone: string,
     *     primary_name: string,
     *     primary_email: string,
     *     primary_phone: string,
     *     industry: string
     * }  $attributes
     */
    public function create(array $attributes): Merchant
    {
        $raw = $this->transport->request('POST', 'merchant/create', $this->envelope($attributes));

        return Merchant::fromArray($raw['data'] ?? []);
    }

    /**
     * Update a merchant.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $sid, array $attributes): Merchant
    {
        $raw = $this->transport->request('PATCH', 'merchant/update', $this->envelope(['sid' => $sid] + $attributes));

        return Merchant::fromArray($raw['data'] ?? []);
    }

    /**
     * Fetch the hosted onboarding form link for a merchant.
     */
    public function getFormLink(string $sid): FormLink
    {
        $raw = $this->transport->request('GET', 'merchant/get-form-link', $this->envelope(['sid' => $sid]));

        return FormLink::fromArray($raw['data'] ?? []);
    }

    /**
     * Fetch where a merchant sits in onboarding, from first contact through to
     * being boarded and able to take money. Use it to follow up an application
     * sent with {@see getFormLink()}. Available to affiliate keys, for their own
     * merchants.
     *
     * Prefer the `application_status_changed` webhook, which carries the same
     * fields and fires on every change; poll this only to reconcile after an
     * outage. A link the merchant never opened leaves them on "lead",
     * "discovery" or "proposal" — no status says an invitation is outstanding.
     */
    public function applicationStatus(string $sid): ApplicationStatus
    {
        $raw = $this->transport->request('GET', 'merchant/application-status', $this->envelope(['sid' => $sid]));

        return ApplicationStatus::fromArray($raw['data'] ?? []);
    }
}
