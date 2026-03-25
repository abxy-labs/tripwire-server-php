<?php

declare(strict_types=1);

namespace Tripwire\Server\Result;

final class ListResult
{
    /**
     * @param array<int, mixed> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $limit,
        public readonly bool $hasMore,
        public readonly ?string $nextCursor = null,
    ) {
    }
}

