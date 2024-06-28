<?php declare(strict_types=1);

namespace Http\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareCollectorInterface
{
    public function addMiddleware(string|MiddlewareInterface $middleware): static;

    public function appendMiddleware(string|MiddlewareInterface $middleware): static;
}
