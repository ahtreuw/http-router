<?php declare(strict_types=1);

namespace Http\Dispatcher;

use Http\RouterInterface;
use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareCollectorTrait
{

    /**
     * @var string[]|MiddlewareInterface[]
     */
    protected array $middlewares = [];

    /**
     * @var string[]|MiddlewareInterface[]
     */
    protected array $appendedMiddlewares = [];

    public function addMiddleware(string|MiddlewareInterface $middleware): RouterInterface&MiddlewareCollectorInterface
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function appendMiddleware(string|MiddlewareInterface $middleware): RouterInterface&MiddlewareCollectorInterface
    {
        array_unshift($this->appendedMiddlewares, $middleware);
        return $this;
    }
}
