<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\Address;
use DimePayments\Sdk\DataObjects\MessageResult;

it('creates an address and returns a typed address', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'address_id' => 77,
            'recipient' => 'John Doe',
            'line_one' => '12 Street Ave',
            'line_two' => 'Suite 100',
            'city' => 'Boulder',
            'state' => 'CO',
            'zip' => '80302',
        ]]),
    ]);

    $address = $client->addresses->create('000010', 'cust-uuid', [
        'recipient' => 'John Doe',
        'line_one' => '12 Street Ave',
        'line_two' => 'Suite 100',
        'city' => 'Boulder',
        'state' => 'CO',
        'zip' => '80302',
    ]);

    expect($address)->toBeInstanceOf(Address::class)
        ->and($address->id)->toBe(77)
        ->and($address->lineOne)->toBe('12 Street Ave');

    expect(sentJson($history))->toBe([
        'data' => [
            'sid' => '000010',
            'uuid' => 'cust-uuid',
            'recipient' => 'John Doe',
            'line_one' => '12 Street Ave',
            'line_two' => 'Suite 100',
            'city' => 'Boulder',
            'state' => 'CO',
            'zip' => '80302',
        ],
    ]);
});

it('reads the id from the address_id key on show and the id key on list', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => ['address_id' => 42, 'line_one' => '1 Main St']]),
        jsonResponse([
            'data' => [['id' => 99, 'line_one' => '2 Main St']],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => null],
        ]),
    ]);

    $shown = $client->addresses->show('000010', 'cust-uuid', 42);
    expect($shown->id)->toBe(42);

    $page = $client->addresses->list('000010', 'cust-uuid');
    expect($page->data[0]->id)->toBe(99);
});

it('paginates addresses across cursors', function () {
    [$client, $history] = fakeClient([
        jsonResponse([
            'data' => [['id' => 1, 'line_one' => 'A']],
            'links' => ['next' => 'http://x/api/address/list?cursor=page2'],
            'meta' => ['per_page' => 500, 'next_cursor' => 'page2', 'prev_cursor' => null],
        ]),
        jsonResponse([
            'data' => [['id' => 2, 'line_one' => 'B']],
            'meta' => ['per_page' => 500, 'next_cursor' => null, 'prev_cursor' => 'page1'],
        ]),
    ]);

    $page = $client->addresses->list('000010', 'cust-uuid');

    expect($page->count())->toBe(1)
        ->and($page->hasMore())->toBeTrue();

    $all = iterator_to_array($page->autoPaging());
    expect($all)->toHaveCount(2)
        ->and($all[0]->id)->toBe(1)
        ->and($all[1]->id)->toBe(2);

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'uuid' => 'cust-uuid'],
    ]);
});

it('deletes an address and returns a message result', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => ['message' => 'Address deleted successfully.']]),
    ]);

    $result = $client->addresses->delete('000010', 'cust-uuid', 42);

    expect($result)->toBeInstanceOf(MessageResult::class)
        ->and($result->message)->toBe('Address deleted successfully.');

    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010', 'uuid' => 'cust-uuid', 'address_id' => 42],
    ]);
});
