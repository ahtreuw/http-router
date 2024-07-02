<?php declare(strict_types=1);

namespace Http;

use Http\Dispatcher\MiddlewareCollectorDecorator;
use Http\Dispatcher\MiddlewareCollectorInterface;
use Http\Exception\MethodNotAllowedException;
use Http\Exception\NotFoundException;
use Http\Path\GroupInterface;
use Http\Path\PathFinder;
use Http\Path\PathFinderInterface;
use Http\Path\PathInterface;
use Http\Path\RouterGroup;
use Http\Path\RouterPath;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class Router implements RouterInterface, MiddlewareInterface
{
    private const SIMPLE_PATHS = 'simple';
    private const COMPLEX_PATHS = 'complex';

    private array $map = [
        self::GET => [self::SIMPLE_PATHS => [], self::COMPLEX_PATHS => []],
        self::HEAD => [self::SIMPLE_PATHS => [], self::COMPLEX_PATHS => []],
        self::POST => [self::SIMPLE_PATHS => [], self::COMPLEX_PATHS => []],
        self::PUT => [self::SIMPLE_PATHS => [], self::COMPLEX_PATHS => []],
        self::DELETE => [self::SIMPLE_PATHS => [], self::COMPLEX_PATHS => []],
        self::CONNECT => [self::SIMPLE_PATHS => [], self::COMPLEX_PATHS => []],
        self::OPTIONS => [self::SIMPLE_PATHS => [], self::COMPLEX_PATHS => []],
        self::TRACE => [self::SIMPLE_PATHS => [], self::COMPLEX_PATHS => []],
        self::PATCH => [self::SIMPLE_PATHS => [], self::COMPLEX_PATHS => []],
        self::CLI => [self::SIMPLE_PATHS => [], self::COMPLEX_PATHS => []],
        self::ANY => [self::SIMPLE_PATHS => [], self::COMPLEX_PATHS => []]
    ];

    /**
     * @var GroupInterface[]
     */
    private array $groups = [];

    public function __construct(private readonly PathFinderInterface $pathFinder = new PathFinder)
    {
    }

    /**
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     */
    public function process(
        ServerRequestInterface  $request,
        RequestHandlerInterface $handler,
        string                  $method = null
    ): ResponseInterface
    {
        if (array_key_exists($method ?: $method = $request->getMethod(), $this->map) === false) {
            throw new MethodNotAllowedException($request, 'Method Not Allowed ' . $method);
        }

        $path = trim($request->getUri()->getPath(), "/ \n\r\t\v\0");

        if ($new = $this->find($request, $method, $path)) {
            $this->map = $this->groups = []; // reset map and groups
            return $handler->handle($new);
        }

        if ($method !== self::ANY) {
            return $this->process($request, $handler, self::ANY);
        }

        throw new NotFoundException($request);
    }

    public function addPath(
        string $method,
        string $path,
        string $requestHandler,
        string ...$groups
    ): RouterInterface&MiddlewareCollectorInterface
    {
        if (array_key_exists($method, $this->map) === false) {
            throw new RuntimeException('Method Not Allowed: ' . $method);
        }

        if (empty($groups)) {
            return $this->createPath($method, $path, $requestHandler);
        }

        $pathInstances = [];
        foreach ($groups as $group) {
            if (array_key_exists($group, $this->groups) === false) {
                throw new RuntimeException('Group Not exists: ' . $group);
            }
            $pathLine = trim($this->groups[$group]->getPath(), '/') . '/' . trim($path, '/');
            $pathInstances[] = $this->createPath($method, $pathLine, $requestHandler, $this->groups[$group]);
        }
        return new MiddlewareCollectorDecorator($this, ...$pathInstances);
    }

    public function addGroup(string $name, string $path = '/'): RouterInterface&MiddlewareCollectorInterface
    {
        return $this->groups[$name] = new RouterGroup($this, $name, $path);
    }

    private function find(
        ServerRequestInterface $request, string $method, string $path
    ): ?ServerRequestInterface
    {
        return $this->pathFinder->find(
            $request,
            $this->map[$method][self::SIMPLE_PATHS],
            $this->map[$method][self::COMPLEX_PATHS],
            $path
        );
    }

    private function addPathToMap(string $method, PathInterface $pathInstance, string $pathLine): void
    {
        if (str_contains($pathLine, '{')) {
            $this->map[$method][self::COMPLEX_PATHS][trim($pathLine, '/')] = $pathInstance;
            return;
        }
        $this->map[$method][self::SIMPLE_PATHS][trim($pathLine, '/')] = $pathInstance;
    }

    private function createPath(
        string         $method,
        string         $path,
        string         $requestHandler,
        GroupInterface $group = null
    ): RouterPath
    {
        $pathInstance = new RouterPath($this, $requestHandler, $group);
        $this->addPathToMap($method, $pathInstance, trim($path, '/'));
        return $pathInstance;
    }
}
