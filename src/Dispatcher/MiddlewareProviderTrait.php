<?php declare(strict_types=1);

namespace Http\Dispatcher;

use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareProviderTrait
{
    public function fetchMiddleware(bool $appended = null): null|string|MiddlewareInterface
    {
        if ($appended === true) {
            return array_shift($this->appendedMiddlewares);
        }
        if ($appended === false) {
            return array_shift($this->middlewares);
        }
        if ($middleware = array_shift($this->appendedMiddlewares)) {
            return $middleware;
        }
        return array_shift($this->middlewares);
    }
}
