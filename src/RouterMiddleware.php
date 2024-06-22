<?php declare(strict_types=1);

namespace Http;

use Http\Exception\MethodNotAllowedException;
use Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class RouterMiddleware implements RouterInterface, MiddlewareInterface
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

    public function __construct(
        private readonly PathFinderInterface $pathFinder = new PathFinder
    )
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
            return $handler->handle($new);
        }

        if ($method !== self::ANY) {
            return $this->process($request, $handler, self::ANY);
        }

        throw new NotFoundException($request);
    }

    public function addPath(string $method, string $path, string $requestHandler): void
    {
        if (array_key_exists($method, $this->map) === false) {
            throw new RuntimeException('Method Not Allowed: ' . $method);
        }
        if (false === str_contains($path, '{')) {
            $this->map[$method][self::SIMPLE_PATHS][trim($path, '/')] = $requestHandler;
            return;
        }
        $this->map[$method][self::COMPLEX_PATHS][trim($path, '/')] = $requestHandler;
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
}
