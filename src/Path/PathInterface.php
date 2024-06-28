<?php declare(strict_types=1);

namespace Http\Path;

use Http\Dispatcher\MiddlewareCollectorInterface;
use Stringable;

interface PathInterface extends Stringable, MiddlewareCollectorInterface
{
    public function getControllerName(): string;

    public function getMethodName(): null|string;

    public function withParams(array $array): PathInterface;

    public function getParams(): array;
}
