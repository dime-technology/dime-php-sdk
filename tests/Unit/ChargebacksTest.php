<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\Chargeback;

/**
 * The chargeback shape returned by list and show.
 *
 * @return array<string, mixed>
 */
function fullChargeback(): array
{
    return [
        'transaction_info_id' => '1134722723',
        'parent_transaction_info_id' => '1132652128',
        'gateway_transaction_id' => null,
        'transaction_number' => '1566',
        'invoice_number' => null,
        'chargeback_date' => '2025-05-04T17:45:05-04:00',
        'merchant_chargeback_date' => '2025-05-04T19:45:05-04:00',
        'transaction_amount' => 391.48,
        'chargeback_amount' => 391.48,
        'card_brand' => 'V',
        'cc_last_four' => '2510',
        'payee_name' => null,
        'days_to_represent' => 0,
        'representment_date' => '2025-05-12T06:26:00-04:00',
        'merchant_representment_date' => '2025-05-05T07:32:01-04:00',
        'representment_status' => 'accepting chargeback',
        'result' => 'Accepting',
        'chargeback_code' => '4',
        'chargeback_response_code' => 'Other fraud - Card Absent Environment',
        'resolved' => false,
    ];
}

it('lists chargebacks and paginates across cursors', function () {
    [$client, $history] = fakeClient([
        jsonResponse([
            'data' => [fullChargeback()],
            'meta' => ['per_page' => 500, 'next_cursor' => 'page2', 'prev_cursor' => null],
        ]),
        jsonResponse([
            'data' => [['transaction_info_id' => '1134722724'] + fullChargeback()],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => 'page1'],
        ]),
    ]);

    $page = $client->chargebacks->list('000010', [
        'start_date' => '2025-05-01 00:00:00',
        'end_date' => '2025-05-31 00:00:00',
        'representment_status' => 'New',
    ]);

    expect($page->data[0])->toBeInstanceOf(Chargeback::class)
        ->and($page->data[0]->transactionInfoId)->toBe('1134722723')
        ->and($page->data[0]->parentTransactionInfoId)->toBe('1132652128')
        ->and($page->data[0]->chargebackAmount)->toBe('391.48')
        ->and($page->data[0]->daysToRepresent)->toBe(0)
        ->and($page->data[0]->gatewayTransactionId)->toBeNull()
        ->and($page->data[0]->resolved)->toBeFalse()
        ->and($page->hasMore())->toBeTrue();

    $all = iterator_to_array($page->autoPaging());
    expect($all)->toHaveCount(2)
        ->and($all[1]->transactionInfoId)->toBe('1134722724');

    expect($history[0]['request']->getMethod())->toBe('GET')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/chargeback/list');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010'],
        'filters' => [
            'start_date' => '2025-05-01 00:00:00',
            'end_date' => '2025-05-31 00:00:00',
            'representment_status' => 'New',
        ],
    ]);
});

it('shows a chargeback, sending its id as a string', function () {
    [$client, $history] = fakeClient([jsonResponse(['data' => ['resolved' => true] + fullChargeback()])]);

    $chargeback = $client->chargebacks->show('000010', 1134722723);

    expect($chargeback)->toBeInstanceOf(Chargeback::class)
        ->and($chargeback->cardBrand)->toBe('V')
        ->and($chargeback->ccLastFour)->toBe('2510')
        ->and($chargeback->result)->toBe('Accepting')
        ->and($chargeback->chargebackResponseCode)->toBe('Other fraud - Card Absent Environment')
        ->and($chargeback->resolved)->toBeTrue();

    expect($history[0]['request']->getUri()->getPath())->toBe('/api/chargeback/show');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'transaction_info_id' => '1134722723'],
    ]);
});

it('returns an empty page when no chargebacks match', function () {
    [$client] = fakeClient([
        jsonResponse([
            'data' => [],
            'links' => ['prev' => null, 'next' => null],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => null],
        ]),
    ]);

    $page = $client->chargebacks->list('000010');

    expect($page->data)->toBe([])
        ->and($page)->toHaveCount(0)
        ->and($page->hasMore())->toBeFalse()
        ->and(iterator_to_array($page->autoPaging()))->toBe([]);
});
