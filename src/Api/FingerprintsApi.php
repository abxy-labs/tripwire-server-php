<?php

declare(strict_types=1);

namespace Tripwire\Server\Api;

use Generator;
use Tripwire\Server\Http\HttpClient;
use Tripwire\Server\Resource\FingerprintDetail;
use Tripwire\Server\Resource\FingerprintSummary;
use Tripwire\Server\Result\ListResult;

final class FingerprintsApi
{
    public function __construct(private readonly HttpClient $http) {}

    public function list(
        ?int $limit = null,
        ?string $cursor = null,
        ?string $search = null,
        ?string $sort = null,
    ): ListResult {
        $response = $this->http->requestJson('GET', '/v1/fingerprints', [
            'limit' => $limit,
            'cursor' => $cursor,
            'search' => $search,
            'sort' => $sort,
        ]);

        $items = [];
        foreach ((array) $response['data'] as $item) {
            $items[] = FingerprintSummary::fromArray((array) $item);
        }

        $pagination = (array) ($response['pagination'] ?? []);

        return new ListResult(
            $items,
            (int) $pagination['limit'],
            (bool) $pagination['hasMore'],
            isset($pagination['nextCursor']) ? (string) $pagination['nextCursor'] : null,
        );
    }

    public function get(string $visitorId): FingerprintDetail
    {
        $response = $this->http->requestJson('GET', '/v1/fingerprints/' . rawurlencode($visitorId));
        return FingerprintDetail::fromArray((array) $response['data']);
    }

    /**
     * @return Generator<int, FingerprintSummary>
     */
    public function iterate(
        ?int $limit = null,
        ?string $search = null,
        ?string $sort = null,
    ): Generator {
        $cursor = null;

        do {
            $page = $this->list($limit, $cursor, $search, $sort);
            foreach ($page->items as $item) {
                yield $item;
            }
            $cursor = $page->hasMore ? $page->nextCursor : null;
        } while ($cursor !== null);
    }
}

