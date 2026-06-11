<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\Customer;

it('creates a customer and returns a typed customer', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'uuid' => 'cus-abc-123',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '5551234567',
            'addr1' => '12 Street Ave',
            'city' => 'Boulder',
            'state' => 'CO',
            'zip' => '80302',
            'country' => 'US',
        ]]),
    ]);

    $customer = $client->customers->create('000010', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->uuid)->toBe('cus-abc-123')
        ->and($customer->firstName)->toBe('Jane')
        ->and($customer->lastName)->toBe('Doe')
        ->and($customer->email)->toBe('jane@example.com')
        ->and($customer->city)->toBe('Boulder');

    // Request was wrapped in the data envelope with the sid merged in.
    $sent = sentJson($history);
    expect($sent)->toBe([
        'data' => ['sid' => '000010', 'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com'],
    ]);
});

it('paginates customers across cursors', function () {
    [$client] = fakeClient([
        jsonResponse([
            'data' => [['uuid' => 'cus-1', 'first_name' => 'Jane', 'last_name' => 'Doe']],
            'links' => ['next' => 'http://x/api/customer/list?cursor=page2'],
            'meta' => ['per_page' => 500, 'next_cursor' => 'page2', 'prev_cursor' => null],
        ]),
        jsonResponse([
            'data' => [['uuid' => 'cus-2', 'first_name' => 'John', 'last_name' => 'Smith']],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => 'page1'],
        ]),
    ]);

    $page = $client->customers->list('000010');

    expect($page->count())->toBe(1)
        ->and($page->hasMore())->toBeTrue()
        ->and($page->perPage)->toBe(500);

    $all = iterator_to_array($page->autoPaging());
    expect($all)->toHaveCount(2)
        ->and($all[0])->toBeInstanceOf(Customer::class)
        ->and($all[0]->uuid)->toBe('cus-1')
        ->and($all[1]->uuid)->toBe('cus-2');
});

it('shows a customer sending filters under the filters envelope key', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'uuid' => 'cus-abc-123',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
        ]]),
    ]);

    $customer = $client->customers->show('000010', ['uuid' => 'cus-abc-123']);

    expect($customer->uuid)->toBe('cus-abc-123')
        ->and($customer->firstName)->toBe('Jane');

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010'],
        'filters' => ['uuid' => 'cus-abc-123'],
    ]);
});
