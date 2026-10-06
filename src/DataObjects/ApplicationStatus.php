<?php

declare(strict_types=1);

namespace DimePayments\Sdk\DataObjects;

use DimePayments\Sdk\Support\Arr;

/**
 * Where a merchant sits in onboarding, as returned by the merchant
 * application-status endpoint (and carried by the `application_status_changed`
 * webhook).
 *
 * `status` is the headline: one of "lead", "discovery", "proposal",
 * "application_in_progress", "underwriting", "live", "cancellation_pending",
 * "churned" or "declined", or null when onboarding has not started.
 * `applicationStatus` is the underlying application record — "draft",
 * "pending_review", "submitted", "approved", "needs_documents" or "failed", or
 * null when no application exists — and is the field to read while `status` is
 * "underwriting": only "needs_documents" needs the merchant to act.
 *
 * `boarded` is the ground truth for "can they take money"; a merchant can be
 * live without the application reading "approved".
 */
final class ApplicationStatus
{
    public function __construct(
        public readonly ?string $sid,
        public readonly ?string $name,
        public readonly ?string $status,
        public readonly ?string $applicationStatus,
        public readonly bool $boarded,
        public readonly ?string $applicationSubmittedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sid: Arr::string($data, 'sid'),
            name: Arr::string($data, 'name'),
            status: Arr::string($data, 'status'),
            applicationStatus: Arr::string($data, 'application_status'),
            boarded: Arr::bool($data, 'boarded'),
            applicationSubmittedAt: Arr::string($data, 'application_submitted_at'),
        );
    }
}
