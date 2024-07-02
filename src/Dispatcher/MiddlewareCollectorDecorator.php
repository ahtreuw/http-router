<?php declare(strict_types=1);

namespace Http\Dispatcher;

use Http\RouterInterface;
use Psr\Http\Server\MiddlewareInterface;

readonly class MiddlewareCollectorDecorator implements RouterInterface, MiddlewareCollectorInterface
{
    private array $collectors;

    public function __construct(
        private RouterInterface          $router,
        MiddlewareCollectorInterface     ...$collectors
    )
    {
        $this->collectors = $collectors;
    }

    public function addMiddleware(MiddlewareInterface|string $middleware): RouterInterface&MiddlewareCollectorInterface
    {
        foreach ($this->collectors as $collector) {
            $collector->addMiddleware($middleware);
        }
        return $this;
    }

    public function appendMiddleware(MiddlewareInterface|string $middleware): RouterInterface&MiddlewareCollectorInterface
    {
        foreach ($this->collectors as $collector) {
            $collector->appendMiddleware($middleware);
        }
        return $this;
    }

    public function addPath(string $method, string $path, string $requestHandler, string ...$groups): RouterInterface&MiddlewareCollectorInterface
    {
        return $this->router->addPath($method, $path, $requestHandler, ...$groups);
    }

    public function addGroup(string $name, string $path = '/'): RouterInterface&MiddlewareCollectorInterface
    {
        return $this->router->addGroup($name, $path);
    }
}
