<?php declare(strict_types=1);

namespace Http\Path;

use Http\Dispatcher\MiddlewareCollectorTrait;
use Http\Dispatcher\MiddlewareProviderInterface;
use Http\Dispatcher\MiddlewareProviderTrait;
use Http\RouterInterface;

class Path implements PathInterface, RouterInterface, MiddlewareProviderInterface
{
    use MiddlewareCollectorTrait, MiddlewareProviderTrait;

    private array $params = [];

    public function __construct(
        private null|RouterInterface $router,
        private readonly string      $requestHandler
    )
    {
    }

    public function getControllerName(): string
    {
        if (str_contains($this->requestHandler, '::')) {
            return strstr($this->requestHandler, '::', true);
        }
        return $this->requestHandler;
    }

    public function getMethodName(): null|string
    {
        if (str_contains($this->requestHandler, '::')) {
            return substr(strstr($this->requestHandler, '::'), 2);
        }
        return null;
    }

    public function __toString(): string
    {
        return $this->requestHandler;
    }

    public function addPath(string $method, string $path, string $requestHandler): PathInterface
    {
        return $this->router->addPath($method, $path, $requestHandler);
    }

    public function withParams(array $array): PathInterface
    {
        $this->params = $array;
        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function reset(): void
    {
        $this->router = null;
    }
}
