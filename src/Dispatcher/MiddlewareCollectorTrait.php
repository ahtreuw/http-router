<?php declare(strict_types=1);

namespace Http\Dispatcher;

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

    public function addMiddleware(string|MiddlewareInterface $middleware): static
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function appendMiddleware(string|MiddlewareInterface $middleware): static
    {
        array_unshift($this->appendedMiddlewares, $middleware);
        return $this;
    }
}
