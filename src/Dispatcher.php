<?php declare(strict_types=1);

namespace Http;

use Http\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Dispatcher implements RouterInterface, RequestHandlerInterface
{
    private array $middlewares = [];

    public function __construct(
        private readonly ContainerInterface                  $container,
        private readonly RouterInterface&MiddlewareInterface $router = new RouterMiddleware
    )
    {
        $this->middlewares[] = $this->router;
    }

    public function addPath(string $method, string $path, string $requestHandler): void
    {
        $this->router->addPath($method, $path, $requestHandler);
    }

    public function addMiddleware(MiddlewareInterface|string $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($middleware = $this->fetchMiddleware()) {
            return $middleware->process($request, $this);
        }

        $requestHandler = $request->getAttribute(RequestHandlerInterface::class);

        $method = null;

        if (str_contains($requestHandler, '::')) {
            [$requestHandler, $method] = explode('::', $requestHandler, 2);
        }

        $controller = $this->createController($requestHandler);

        return $this->process($request, $controller, $method ?: null);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function fetchMiddleware(): ?MiddlewareInterface
    {
        $middleware = array_shift($this->middlewares);

        if ($middleware && false === is_object($middleware)) {
            return $this->container->get($middleware);
        }

        return $middleware;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function createController(string $controllerName): object
    {
        return $this->container->get($controllerName);
    }

    /**
     * @throws NotFoundException
     */
    private function process(
        ServerRequestInterface $request,
        object                 $controller,
        null|string            $method
    ): ResponseInterface
    {
        if ($controller instanceof RequestHandlerInterface) {
            return $controller->handle($request);
        }
        if (method_exists($controller, 'setServerRequest')) {
            $controller->setServerRequest($request);
        }

        $params = $request->getAttribute('params');
        $paramsCount = count($params);

        if (is_null($method) && 0 < $paramsCount && method_exists($controller, 'details')) {
            return $controller->details(...$params);
        }
        if (is_null($method) && method_exists($controller, 'index')) {
            return $controller->index(...$params);
        }
        if (method_exists($controller, $method) === false) {
            throw new NotFoundException($request);
        }
        return $controller->$method(...$params);
    }
}
