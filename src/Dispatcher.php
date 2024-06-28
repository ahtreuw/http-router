<?php declare(strict_types=1);

namespace Http;

use Http\Dispatcher\MiddlewareCollectorInterface;
use Http\Dispatcher\MiddlewareCollectorTrait;
use Http\Dispatcher\MiddlewareProviderInterface;
use Http\Dispatcher\RouterMiddleware;
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
        RouterInterface&MiddlewareInterface      $router = new RouterMiddleware,
        private readonly RequestHandlerInterface $handler = new RequestHandler
    )
    {
        $this->addMiddleware($this->router = $router);
    }

    public function addPath(string $method, string $path, string $requestHandler): PathInterface
    {
        if (is_null($this->router)) {
            throw new RuntimeException('After the request handler is running, the router is unavailable.');
        }
        return $this->router->addPath($method, $path, $requestHandler);
    }

    /**
     * @throws Throwable
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->router = null;

        if ($response = $this->processMiddlewares($request, $path = $this->getPath($request))) {
            return $response;
        }

        return $this->handler->handle($request
            ->withAttribute(ControllerInterface::class, $this->createController($path)));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function fetchMiddleware(): ?MiddlewareInterface
    {
        if (is_null($middleware = array_shift($this->appendedMiddlewares))) {
            $middleware = array_shift($this->middlewares);
        }

        if ($middleware && false === is_object($middleware)) {
            return $this->container->get($middleware);
        }

        return $middleware;
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
    private function processMiddlewares(ServerRequestInterface $request, null|PathInterface $path): ?ResponseInterface
    {
        if ($path instanceof MiddlewareProviderInterface && $middleware = $path->fetchMiddleware(true)) {
            return $middleware->process($request, $this);
        }

        if ($middleware = $this->fetchMiddleware()) {
            return $middleware->process($request, $this);
        }

        if ($path instanceof MiddlewareProviderInterface && $middleware = $path->fetchMiddleware(false)) {
            return $middleware->process($request, $this);
        }

        return null;
    }

}
