<?php declare(strict_types=1);

namespace Http\Path;

use Http\Dispatcher\MiddlewareProviderInterface;

class Path implements PathInterface
{
    public function __construct(
        protected readonly string              $requestHandler,
        protected readonly array               $params,
        protected readonly null|GroupInterface $group
    )
    {
    }

    public function getRequestHandler(): string
    {
        return $this->requestHandler;
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

    public function getParams(): array
    {
        return $this->params;
    }

    public function getGroup(): null|GroupInterface|MiddlewareProviderInterface
    {
        return $this->group;
    }

    public function __toString(): string
    {
        return $this->requestHandler;
    }
}