<?php

declare(strict_types=1);

namespace Tripwire\Server\Tests\Support;

use Nyholm\Psr7\Response;

final class JsonResponse
{
    /**
     * @param array<string, mixed> $headers
     */
    public static function create(mixed $body, int $status = 200, array $headers = []): Response
    {
        return new Response(
            $status,
            ['content-type' => 'application/json'] + $headers,
            json_encode($body, JSON_THROW_ON_ERROR),
        );
    }
}

