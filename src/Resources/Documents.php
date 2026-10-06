<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\DataObjects\Document;
use DimePayments\Sdk\DataObjects\DocumentUploadResult;
use DimePayments\Sdk\Exceptions\NotFoundException;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * Document endpoints: sending a merchant's documents to Dime and listing what
 * is held for them.
 *
 * One endpoint serves underwriting paperwork, identity verification, and
 * evidence contesting a chargeback, told apart by `doc_type` ("Verification",
 * "FraudHolds", "Underwriting" or "RetrievalRequest" — use the last for
 * chargeback evidence). Uploading does not send a document to the processor:
 * Dime's team reviews it and forwards it.
 *
 * Every method takes the merchant `$sid` explicitly.
 */
final class Documents extends AbstractResource
{
    /**
     * Upload one or more documents (up to 10 per call, each 9 MB or smaller,
     * as PDF, JPG, PNG, DOC, DOCX or RTF). The server also caps the combined
     * size of a request; on a 413, send fewer files per call.
     *
     * Each file is either a path on disk, or `['contents' => ..., 'filename'
     * => ...]` for content already in memory or in a stream. To attach evidence
     * to a dispute, pass the chargeback's `transaction_info_id` as
     * `chargeback_transaction_info_id`.
     *
     * Files are stored independently: if some fail, the rest are kept and listed
     * in the result, and the failures appear under `failed` to re-send.
     *
     * The SDK sends this as `multipart/form-data`, the only non-JSON request in
     * the API.
     *
     * @param  array{
     *     doc_type: 'Verification'|'FraudHolds'|'Underwriting'|'RetrievalRequest',
     *     chargeback_transaction_info_id?: int|string
     * }  $attributes
     * @param  array<int, string|array{contents: string|resource|StreamInterface, filename: string}>  $files
     *
     * @throws InvalidArgumentException When a file path cannot be read.
     */
    public function upload(string $sid, array $attributes, array $files): DocumentUploadResult
    {
        $multipart = [];

        foreach (['sid' => $sid] + $attributes as $key => $value) {
            if ($value !== null) {
                $multipart[] = ['name' => "data[{$key}]", 'contents' => (string) $value];
            }
        }

        foreach ($files as $file) {
            $multipart[] = ['name' => 'files[]'] + self::filePart($file);
        }

        $raw = $this->transport->multipart('POST', 'document/upload', $multipart);

        return DocumentUploadResult::fromArray($raw['data'] ?? []);
    }

    /**
     * List the documents held for a merchant — those uploaded through the API
     * and those added by Dime's team or through the merchant application. This
     * endpoint is not paginated.
     *
     * When there are none (or none match), the API answers 404, raised as a
     * {@see NotFoundException}, rather than returning an empty list.
     *
     * @param  array{
     *     doc_type?: 'Verification'|'FraudHolds'|'Underwriting'|'RetrievalRequest',
     *     chargeback_transaction_info_id?: int|string
     * }  $filters
     * @return array<int, Document>
     */
    public function list(string $sid, array $filters = []): array
    {
        if (isset($filters['chargeback_transaction_info_id'])) {
            // The API only accepts this reference as a string.
            $filters['chargeback_transaction_info_id'] = (string) $filters['chargeback_transaction_info_id'];
        }

        $raw = $this->transport->request('GET', 'document/list', $this->envelope(['sid' => $sid], $filters));

        return array_map(
            static fn (array $document): Document => Document::fromArray($document),
            array_values($raw['data'] ?? []),
        );
    }

    /**
     * Normalise one file argument into a multipart part's contents and filename.
     *
     * @param  string|array{contents: string|resource|StreamInterface, filename: string}  $file
     * @return array{contents: string|resource|StreamInterface, filename: string}
     */
    private static function filePart(string|array $file): array
    {
        if (is_array($file)) {
            return ['contents' => $file['contents'], 'filename' => $file['filename']];
        }

        if (! is_file($file) || ! is_readable($file)) {
            throw new InvalidArgumentException("Cannot read the file to upload: {$file}");
        }

        $handle = fopen($file, 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException("Cannot open the file to upload: {$file}");
        }

        return ['contents' => $handle, 'filename' => basename($file)];
    }
}
