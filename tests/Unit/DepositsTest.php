<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\DepositGroup;
use DimePayments\Sdk\DataObjects\DepositWithTransactions;
use DimePayments\Sdk\DataObjects\Transaction;

it('paginates deposits across cursors', function () {
    [$client, $history] = fakeClient([
        jsonResponse([
            'data' => [
                ['transaction_date' => '2026-01-01', 'sweep_id' => 'sw_1', 'net_amount' => '25.0000', 'type' => 'CC'],
            ],
            'meta' => ['per_page' => 500, 'next_cursor' => 'page2', 'prev_cursor' => null],
        ]),
        jsonResponse([
            'data' => [
                ['transaction_date' => '2026-01-02', 'sweep_id' => 'sw_2', 'net_amount' => '30.0000', 'type' => 'CC'],
            ],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => 'page1'],
        ]),
    ]);

    $page = $client->deposits->list('000010', ['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);

    expect($page->count())->toBe(1)
        ->and($page->hasMore())->toBeTrue()
        ->and($page->data[0]->sweepId)->toBe('sw_1')
        ->and($page->data[0]->netAmount)->toBe('25.0000');

    $all = iterator_to_array($page->autoPaging());
    expect($all)->toHaveCount(2)
        ->and($all[1]->sweepId)->toBe('sw_2');

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010'],
        'filters' => ['start_date' => '2026-01-01', 'end_date' => '2026-01-31'],
    ]);
});

it('shows a deposit with typed transactions', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'sid' => '000010',
            'sweep_id' => 'sw_1',
            'transaction_info_id' => '42',
            'type' => 'CC',
            'countOfTransactions' => 2,
            'transTotal' => '74.99',
            'transactions' => [
                ['transaction_type' => 'Credit Card', 'transaction_number' => '1', 'pending' => false],
                ['transaction_type' => 'ACH', 'transaction_number' => '2', 'pending' => true],
            ],
        ]]),
    ]);

    $deposit = $client->deposits->show('000010', ['transaction_info_id' => 42]);

    expect($deposit)->toBeInstanceOf(DepositWithTransactions::class)
        ->and($deposit->countOfTransactions)->toBe(2)
        ->and($deposit->transTotal)->toBe('74.99')
        ->and($deposit->transactions)->toHaveCount(2)
        ->and($deposit->transactions[0])->toBeInstanceOf(Transaction::class)
        ->and($deposit->transactions[0]->transactionType)->toBe('Credit Card')
        ->and($deposit->transactions[1]->transactionType)->toBe('ACH');

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'transaction_info_id' => 42],
    ]);
});

it('lists deposits with transactions as a deposit group', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'sid' => '000010',
            'count' => 1,
            'deposits' => [
                'sw_1' => [
                    'sid' => '000010',
                    'sweep_id' => 'sw_1',
                    'type' => 'CC',
                    'countOfTransactions' => 1,
                    'transTotal' => '25.00',
                    'transactions' => [
                        ['transaction_type' => 'Credit Card', 'transaction_number' => '1', 'pending' => false],
                    ],
                ],
            ],
        ]]),
    ]);

    $group = $client->deposits->listWithTransactions('000010', [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ]);

    expect($group)->toBeInstanceOf(DepositGroup::class)
        ->and($group->count)->toBe(1)
        ->and($group->deposits)->toHaveCount(1)
        ->and($group->deposits[0])->toBeInstanceOf(DepositWithTransactions::class)
        ->and($group->deposits[0]->type)->toBe('CC')
        ->and($group->deposits[0]->transactions[0]->transactionType)->toBe('Credit Card');

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010'],
        'filters' => ['start_date' => '2026-01-01', 'end_date' => '2026-01-31'],
    ]);
});
