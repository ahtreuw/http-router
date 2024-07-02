<?php declare(strict_types=1);

namespace Http;

use Http\Dispatcher\MiddlewareCollectorInterface;
use Http\Dispatcher\MiddlewareCollectorTrait;
use Http\Dispatcher\MiddlewareProviderInterface;
use Http\Path\Group;
use Http\Path\GroupInterface;
use Http\Path\ParametersInterface;
use Http\Path\Path;
use Http\Path\PathInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Throwable;

class Dispatcher implements RouterInterface, RequestHandlerInterface, MiddlewareCollectorInterface
{
    use MiddlewareCollectorTrait;

    private null|(RouterInterface&MiddlewareInterface) $router;

    public function __construct(
        private readonly ContainerInterface      $container,
        RouterInterface&MiddlewareInterface      $router = new Router,
        private readonly RequestHandlerInterface $handler = new RequestHandler
    )
    {
        $this->router = $router;
    }

    public function addPath(string $method, string $path, string $requestHandler, string ...$groups): RouterInterface&MiddlewareCollectorInterface
    {
        if (is_null($this->router)) {
            throw new RuntimeException('After the request handler is running, the router is unavailable.');
        }
        return $this->router->addPath($method, $path, $requestHandler, ...$groups);
    }

    public function addGroup(string $name, string $path = '/'): RouterInterface&MiddlewareCollectorInterface
    {
        return $this->router->addGroup($name, $path);
    }

    /**
     * @throws Throwable
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($router = $this->router) {
            $this->router = null;
            return $router->process($request, $this);
        }

        if (method_exists($this->container, 'set')) {
            $this->container->set(ServerRequestInterface::class, $request);
        }

        if ($middleware = $this->fetchMiddleware($path = $this->getPath($request))) {
            return $middleware->process($request, $this);
        }

        $request = $this->preparePath($request, $path);

        if (method_exists($this->container, 'set')) {
            $this->container->set(ServerRequestInterface::class, $request);
        }

        return $this->handler->handle($request
            ->withAttribute(ControllerInterface::class, $this->createController($path)));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function fetchMiddleware(
        null|(PathInterface&MiddlewareProviderInterface) $path
    ): null|MiddlewareInterface
    {
        if ($middleware = $this->fetchPathMiddleware(true, $path)) {
            return $this->prepareMiddleware($middleware);
        }
        if ($middleware = array_shift($this->appendedMiddlewares)) {
            return $this->prepareMiddleware($middleware);
        }
        if ($middleware = array_shift($this->middlewares)) {
            return $this->prepareMiddleware($middleware);
        }
        if ($middleware = $this->fetchPathMiddleware(false, $path)) {
            return $this->prepareMiddleware($middleware);
        }
        return null;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createController(PathInterface $path): object
    {
        return $this->container->get($path->getControllerName());
    }

    private function getPath(ServerRequestInterface $serverRequest): ?PathInterface
    {
        return $serverRequest->getAttribute(PathInterface::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function prepareMiddleware(null|string|MiddlewareInterface $middleware): ?MiddlewareInterface
    {
        if ($middleware && false === is_object($middleware)) {
            return $this->container->get($middleware);
        }
        return $middleware;
    }

    private function preparePath(ServerRequestInterface $request, PathInterface $path): ServerRequestInterface
    {
        return $request->withAttribute(PathInterface::class, new Path($path->getRequestHandler(),
            $request->getAttribute(ParametersInterface::class, []), $this->createGroup($path)));
    }

    private function createGroup(PathInterface $pathInstance): null|GroupInterface
    {
        if ($group = $pathInstance->getGroup()) {
            return new Group($group->getName(), $group->getPath());
        }
        return null;
    }

    private function fetchPathMiddleware(
        bool                                             $appended,
        (MiddlewareProviderInterface&PathInterface)|null $path
    ): null|string|MiddlewareInterface
    {
        if (is_null($path)) {
            return null;
        }

        if ($appended === false && ($group = $path->getGroup()) &&
            $middleware = $this->fetchGroupMiddleware(false, $group)) {
            return $middleware;
        }

        if ($middleware = $path->fetchMiddleware($appended)) {
            return $middleware;
        }

        if ($appended === true && ($group = $path->getGroup()) &&
            $middleware = $this->fetchGroupMiddleware(true, $group)) {
            return $middleware;
        }

        return null;
    }

    private function fetchGroupMiddleware(
        bool           $appended,
        GroupInterface $group
    ): null|string|MiddlewareInterface
    {
        if ($group instanceof MiddlewareProviderInterface === false) {
            throw new RuntimeException('Group MUST be instance of ' . MiddlewareProviderInterface::class);
        }
        return $group->fetchMiddleware($appended);
    }
}
