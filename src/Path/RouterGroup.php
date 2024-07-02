<?php declare(strict_types=1);

namespace Http\Path;

use Http\Dispatcher\MiddlewareCollectorInterface;
use Http\Dispatcher\MiddlewareCollectorTrait;
use Http\Dispatcher\MiddlewareProviderInterface;
use Http\Dispatcher\MiddlewareProviderTrait;
use Http\RouterInterface;

class RouterGroup extends Group implements GroupInterface, RouterInterface, MiddlewareCollectorInterface, MiddlewareProviderInterface
{
    use MiddlewareCollectorTrait, MiddlewareProviderTrait;

    public function __construct(
        private readonly RouterInterface $router,
        string                  $name,
        string                  $path
    )
    {
        parent::__construct($name, $path);
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