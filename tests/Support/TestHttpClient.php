<?php

declare(strict_types=1);

namespace Tripwire\Server\Tests\Support;

use Closure;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class TestHttpClient implements ClientInterface
{
    /**
     * @param Closure(RequestInterface): ResponseInterface $handler
     */
    public function __construct(private readonly Closure $handler)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return ($this->handler)($request);
    }
}

