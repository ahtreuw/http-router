<?php declare(strict_types=1);

namespace Http\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareProviderInterface
{
    public function fetchMiddleware(bool $appended = null): null|string|MiddlewareInterface;
}
