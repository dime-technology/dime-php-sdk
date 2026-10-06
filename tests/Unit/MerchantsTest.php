<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\ApplicationStatus;
use DimePayments\Sdk\DataObjects\FormLink;
use DimePayments\Sdk\DataObjects\Merchant;

it('shows a merchant and returns a typed merchant', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'name' => 'Acme Co',
            'sid' => '000010',
            'mcc' => '5812',
            'slug' => 'acme-co',
            'active' => true,
            'g_pay' => true,
            'a_pay' => false,
            'pci_compliance' => true,
            'primary_email' => 'owner@acme.test',
        ]]),
    ]);

    $merchant = $client->merchants->show('000010');

    expect($merchant)->toBeInstanceOf(Merchant::class)
        ->and($merchant->sid)->toBe('000010')
        ->and($merchant->name)->toBe('Acme Co')
        ->and($merchant->active)->toBeTrue()
        ->and($merchant->gPay)->toBeTrue()
        ->and($merchant->aPay)->toBeFalse()
        ->and($merchant->pciCompliance)->toBeTrue();

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010'],
    ]);
});

it('paginates merchants across cursors', function () {
    [$client] = fakeClient([
        jsonResponse([
            'data' => [['sid' => '000010', 'name' => 'One']],
            'links' => ['next' => 'http://x/api/merchant/list?cursor=page2'],
            'meta' => ['per_page' => 500, 'next_cursor' => 'page2', 'prev_cursor' => null],
        ]),
        jsonResponse([
            'data' => [['sid' => '000011', 'name' => 'Two']],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => 'page1'],
        ]),
    ]);

    $page = $client->merchants->list(['start_date' => '2026-01-01']);

    expect($page->count())->toBe(1)
        ->and($page->hasMore())->toBeTrue()
        ->and($page->perPage)->toBe(500);

    $all = iterator_to_array($page->autoPaging());
    expect($all)->toHaveCount(2)
        ->and($all[0]->sid)->toBe('000010')
        ->and($all[1]->sid)->toBe('000011');
});

it('returns the hosted form link for a merchant', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['link' => 'https://forms.dime.test/abc123']]),
    ]);

    $formLink = $client->merchants->getFormLink('000010');

    expect($formLink)->toBeInstanceOf(FormLink::class)
        ->and($formLink->link)->toBe('https://forms.dime.test/abc123');

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010'],
    ]);
});

it('returns a merchant\'s onboarding status', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'sid' => '00069',
            'name' => 'Acme Inc',
            'status' => 'underwriting',
            'application_status' => 'needs_documents',
            'boarded' => false,
            'application_submitted_at' => '2024-01-15T14:02:11+00:00',
        ]]),
    ]);

    $status = $client->merchants->applicationStatus('00069');

    expect($status)->toBeInstanceOf(ApplicationStatus::class)
        ->and($status->sid)->toBe('00069')
        ->and($status->name)->toBe('Acme Inc')
        ->and($status->status)->toBe('underwriting')
        ->and($status->applicationStatus)->toBe('needs_documents')
        ->and($status->boarded)->toBeFalse()
        ->and($status->applicationSubmittedAt)->toBe('2024-01-15T14:02:11+00:00');

    expect($history[0]['request']->getMethod())->toBe('GET')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/merchant/application-status');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '00069'],
    ]);
});

it('reads an application status that has not started as nulls', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => [
            'sid' => '00069',
            'name' => 'Acme Inc',
            'status' => 'lead',
            'application_status' => null,
            'boarded' => false,
            'application_submitted_at' => null,
        ]]),
    ]);

    $status = $client->merchants->applicationStatus('00069');

    expect($status->status)->toBe('lead')
        ->and($status->applicationStatus)->toBeNull()
        ->and($status->applicationSubmittedAt)->toBeNull();
});
