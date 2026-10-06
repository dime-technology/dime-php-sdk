<?php

declare(strict_types=1);

use DimePayments\Sdk\DataObjects\Document;
use DimePayments\Sdk\DataObjects\DocumentUploadResult;
use DimePayments\Sdk\Exceptions\NotFoundException;
use Psr\Http\Message\RequestInterface;

it('uploads documents as multipart form data with bracketed field names', function () {
    $path = tempnam(sys_get_temp_dir(), 'dime');
    file_put_contents($path, '%PDF-1.4 receipt');

    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            'message' => '2 documents uploaded.',
            'documents' => [
                ['uuid' => 'uuid-1', 'file_name' => basename($path), 'doc_type' => 'RetrievalRequest', 'size' => 16],
                ['uuid' => 'uuid-2', 'file_name' => 'statement.pdf', 'doc_type' => 'RetrievalRequest', 'size' => 9],
            ],
        ]]),
    ]);

    $result = $client->documents->upload('000010', [
        'doc_type' => 'RetrievalRequest',
        'chargeback_transaction_info_id' => 1134722723,
    ], [
        $path,
        ['contents' => '%PDF-1.4', 'filename' => 'statement.pdf'],
    ]);

    unlink($path);

    expect($result)->toBeInstanceOf(DocumentUploadResult::class)
        ->and($result->message)->toBe('2 documents uploaded.')
        ->and($result->documents)->toHaveCount(2)
        ->and($result->documents[0])->toBeInstanceOf(Document::class)
        ->and($result->documents[0]->uuid)->toBe('uuid-1')
        ->and($result->documents[0]->size)->toBe(16)
        ->and($result->documents[0]->uploadedAt)->toBeNull()
        ->and($result->failed)->toBe([]);

    /** @var RequestInterface $request */
    $request = $history[0]['request'];
    $body = (string) $request->getBody();

    expect($request->getMethod())->toBe('POST')
        ->and($request->getUri()->getPath())->toBe('/api/document/upload')
        ->and($request->getHeaderLine('Content-Type'))->toStartWith('multipart/form-data; boundary=')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer test-token')
        ->and($body)->toContain("name=\"data[sid]\"\r\nContent-Length: 6\r\n\r\n000010")
        ->and($body)->toContain("name=\"data[doc_type]\"\r\nContent-Length: 16\r\n\r\nRetrievalRequest")
        ->and($body)->toContain("name=\"data[chargeback_transaction_info_id]\"\r\nContent-Length: 10\r\n\r\n1134722723")
        ->and($body)->toContain('name="files[]"; filename="'.basename($path).'"')
        ->and($body)->toContain('%PDF-1.4 receipt')
        ->and($body)->toContain('name="files[]"; filename="statement.pdf"');
});

it('reports files that could not be stored', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => [
            'message' => '1 document uploaded. 1 could not be stored.',
            'documents' => [
                ['uuid' => 'uuid-1', 'file_name' => 'receipt.pdf', 'doc_type' => 'Underwriting', 'size' => 20841],
            ],
            'failed' => [
                ['file_name' => 'statement.pdf', 'reason' => 'The file could not be stored. Send it again.'],
            ],
        ]]),
    ]);

    $result = $client->documents->upload('000010', ['doc_type' => 'Underwriting'], [
        ['contents' => 'a', 'filename' => 'receipt.pdf'],
        ['contents' => 'b', 'filename' => 'statement.pdf'],
    ]);

    expect($result->documents)->toHaveCount(1)
        ->and($result->failed)->toHaveCount(1)
        ->and($result->failed[0]->fileName)->toBe('statement.pdf')
        ->and($result->failed[0]->reason)->toBe('The file could not be stored. Send it again.');
});

it('refuses a file path it cannot read', function () {
    [$client] = fakeClient([]);

    $client->documents->upload('000010', ['doc_type' => 'Underwriting'], ['/no/such/file.pdf']);
})->throws(InvalidArgumentException::class, 'Cannot read the file to upload: /no/such/file.pdf');

it('lists documents with filters', function () {
    [$client, $history] = fakeClient([
        jsonResponse(['data' => [
            [
                'uuid' => 'uuid-1',
                'file_name' => 'receipt.pdf',
                'doc_type' => 'RetrievalRequest',
                'chargeback_transaction_info_id' => '1134722723',
                'size' => 20841,
                'uploaded_at' => '2026-09-18T11:35:01+00:00',
                'uploaded_via' => 'api',
                'sent_to_processor' => null,
            ],
            [
                'uuid' => 'uuid-2',
                'file_name' => 'license.png',
                'doc_type' => 'RetrievalRequest',
                'chargeback_transaction_info_id' => '1134722723',
                'size' => 1024,
                'uploaded_at' => '2026-09-17T09:00:00+00:00',
                'uploaded_via' => null,
                'sent_to_processor' => ['2026-09-19 10:00:00', '00'],
            ],
        ]]),
    ]);

    $documents = $client->documents->list('000010', [
        'doc_type' => 'RetrievalRequest',
        'chargeback_transaction_info_id' => 1134722723,
    ]);

    expect($documents)->toHaveCount(2)
        ->and($documents[0])->toBeInstanceOf(Document::class)
        ->and($documents[0]->chargebackTransactionInfoId)->toBe('1134722723')
        ->and($documents[0]->uploadedVia)->toBe('api')
        ->and($documents[0]->sentToProcessorAt)->toBeNull()
        ->and($documents[0]->processorResponse)->toBeNull()
        ->and($documents[1]->sentToProcessorAt)->toBe('2026-09-19 10:00:00')
        ->and($documents[1]->processorResponse)->toBe('00');

    expect($history[0]['request']->getMethod())->toBe('GET')
        ->and($history[0]['request']->getUri()->getPath())->toBe('/api/document/list');
    expect(sentJson($history))->toBe([
        'data' => ['sid' => '000010'],
        'filters' => ['doc_type' => 'RetrievalRequest', 'chargeback_transaction_info_id' => '1134722723'],
    ]);
});

it('raises NotFoundException when the merchant has no documents', function () {
    [$client] = fakeClient([
        jsonResponse(['data' => ['message' => 'No documents found']], 404),
    ]);

    $client->documents->list('000010');
})->throws(NotFoundException::class, 'No documents found');
