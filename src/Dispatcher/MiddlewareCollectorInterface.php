<?php declare(strict_types=1);

namespace Http\Dispatcher;

use Http\RouterInterface;
use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareCollectorInterface
{
    public function addMiddleware(string|MiddlewareInterface $middleware): RouterInterface&MiddlewareCollectorInterface;

    public function appendMiddleware(string|MiddlewareInterface $middleware): RouterInterface&MiddlewareCollectorInterface;
}
