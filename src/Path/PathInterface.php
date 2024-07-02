<?php declare(strict_types=1);

namespace Http\Path;

use Http\Dispatcher\MiddlewareProviderInterface;
use Stringable;

interface PathInterface extends Stringable
{
    public function getRequestHandler(): string;

    public function getControllerName(): string;

    public function getMethodName(): null|string;

    public function getParams(): array;

    public function getGroup(): null|GroupInterface|MiddlewareProviderInterface;
}
