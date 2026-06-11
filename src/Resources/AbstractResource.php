<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Resources;

use DimePayments\Sdk\Http\Transport;
use DimePayments\Sdk\Pagination\CursorPage;

/**
 * Shared behaviour for every API resource: request envelope construction and
 * cursor-pagination assembly.
 */
abstract class AbstractResource
{
    public function __construct(protected readonly Transport $transport) {}

    /**
     * Build the standard request envelope, dropping empty `data`/`filters` groups.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function envelope(array $data = [], array $filters = []): array
    {
        $body = [];

        if ($data !== []) {
            $body['data'] = self::pruneNulls($data);
        }

        if ($filters !== []) {
            $body['filters'] = self::pruneNulls($filters);
        }

        return $body;
    }

    /**
     * Issue a list request and assemble a {@see CursorPage}, wiring up a fetcher
     * so callers can transparently page forward.
     *
     * @param  array<string, mixed>  $body
     * @param  callable(array<string, mixed>): mixed  $map  Maps one raw item to a DTO.
     * @param  array<string, scalar>  $query
     * @return CursorPage<mixed>
     */
    protected function paginate(string $method, string $path, array $body, callable $map, array $query = []): CursorPage
    {
        $raw = $this->transport->request($method, $path, $body, $query);

        /** @var array<int, array<string, mixed>> $items */
        $items = array_values($raw['data'] ?? []);

        /** @var array<string, mixed> $meta */
        $meta = is_array($raw['meta'] ?? null) ? $raw['meta'] : [];

        return new CursorPage(
            data: array_map($map, $items),
            nextCursor: self::cursor($meta, 'next_cursor'),
            prevCursor: self::cursor($meta, 'prev_cursor'),
            perPage: isset($meta['per_page']) && is_numeric($meta['per_page']) ? (int) $meta['per_page'] : null,
            path: isset($meta['path']) && is_string($meta['path']) ? $meta['path'] : null,
            fetcher: fn (string $cursor): CursorPage => $this->paginate($method, $path, $body, $map, ['cursor' => $cursor]),
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private static function cursor(array $meta, string $key): ?string
    {
        $value = $meta[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function pruneNulls(array $values): array
    {
        return array_filter($values, static fn ($value): bool => $value !== null);
    }
}
