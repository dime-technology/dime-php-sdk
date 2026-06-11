<?php

declare(strict_types=1);

namespace DimePayments\Sdk\Pagination;

use ArrayIterator;
use Closure;
use Countable;
use Generator;
use IteratorAggregate;
use Traversable;

/**
 * A single page of a cursor-paginated list endpoint.
 *
 * The Dime API paginates list endpoints with a cursor (500 items per page by
 * default) and returns `links` and `meta` alongside the `data` array. Call
 * {@see next()} to fetch the following page, or {@see autoPaging()} to iterate
 * every item across every page transparently.
 *
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
final class CursorPage implements Countable, IteratorAggregate
{
    /**
     * @param  array<int, T>  $data  The page's items (already mapped to DTOs).
     * @param  (Closure(string): CursorPage<T>)|null  $fetcher  Fetches a page for a given cursor.
     */
    public function __construct(
        public readonly array $data,
        public readonly ?string $nextCursor = null,
        public readonly ?string $prevCursor = null,
        public readonly ?int $perPage = null,
        public readonly ?string $path = null,
        private readonly ?Closure $fetcher = null,
    ) {}

    public function hasMore(): bool
    {
        return $this->nextCursor !== null && $this->fetcher !== null;
    }

    /**
     * Fetch the next page, or null if this is the last page.
     *
     * @return CursorPage<T>|null
     */
    public function next(): ?self
    {
        if (! $this->hasMore()) {
            return null;
        }

        /** @var Closure(string): CursorPage<T> $fetcher */
        $fetcher = $this->fetcher;

        return $fetcher($this->nextCursor);
    }

    /**
     * Lazily iterate every item across all remaining pages.
     *
     * @return Generator<int, T>
     */
    public function autoPaging(): Generator
    {
        $page = $this;

        while ($page !== null) {
            // Yield without `yield from` so keys stay sequential across pages
            // (each page's items are 0-indexed and would otherwise collide).
            foreach ($page->data as $item) {
                yield $item;
            }

            $page = $page->next();
        }
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }
}
