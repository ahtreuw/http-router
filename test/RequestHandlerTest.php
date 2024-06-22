<?php declare(strict_types=1);

namespace Http\Test;

use Http\Exception\NotFoundException;
use Http\Dispatcher;
use Http\RouterInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\RuntimeException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class RequestHandlerTest extends TestCase
{
    private ContainerInterface&MockObject $container;

    private RequestHandlerInterface $handler;

    private RouterInterface&MiddlewareInterface&MockObject $router;


    public static function customControllerProvider(): array
    {
        return [
            ['index', '', []],
            ['index', 'index', ['id' => 13]],
            ['details', '', [12]],
            ['customMethod', 'customMethod', [12, 'hello', 13.3]],
            ['customMethod', 'customMethod', ['message' => 'hello-world', 'number' => 13.3, 'id' => 7]],
        ];
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        /** @var RouterInterface&MiddlewareInterface&MockObject $router */
        $router = $this->createMockForIntersectionOfInterfaces([RouterInterface::class, MiddlewareInterface::class]);

        $this->handler = new Dispatcher($this->container, $this->router = $router);
    }

    /**
     * @dataProvider customControllerProvider
     *
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundException
     */
    public function testCustomController(string $method, string $predefinedName, array $params): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $this->router->expects($this->once())->method('process')->with($request, $this->handler)
            ->willReturnCallback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                return $handler->handle($request);
            });

        $myClass = $this->createMock(TestMockInterface::class);

        $myClass->expects($this->exactly(1))
            ->method($method)
            ->willReturnCallback(function (int $id = null, string $message = null, float $number = null) use ($params) {
                $parameters = array_filter(['id' => $id, 'message' => $message, 'number' => $number]);
                if ($params != $parameters && $params != array_values($parameters)) {
                    throw new RuntimeException('InValid params');
                }
                return $this->createMock(ResponseInterface::class);
            });

        $this->container->expects($this->exactly(1))->method('get')
            ->willReturnCallback(function (string $id) use ($myClass) {
                if ('MyClassName' === $id) {
                    return $myClass;
                }
                return null;
            });

        $request->expects($this->exactly(2))->method('getAttribute')
            ->willReturnCallback(function ($name) use ($params, $predefinedName) {
                if ($name === RequestHandlerInterface::class) {
                    return $predefinedName ? 'MyClassName::' . $predefinedName : 'MyClassName';
                }
                return $params;
            });

        $this->handler->handle($request);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundException
     */
    public function testAddMiddleware(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $middleware = $this->createMock(MiddlewareInterface::class);

        $this->router->expects($this->once())->method('process')
            ->with($request, $this->handler)
            ->willReturnCallback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                return $handler->handle($request);
            });

        $middleware->expects($this->once())->method('process')
            ->willReturnCallback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                return $handler->handle($request);
            });

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->once())->method('handle')
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->container->expects($this->once())->method('get')
            ->willReturn($requestHandler);

        $request->expects($this->once())->method('getAttribute')->willReturn(RequestHandlerInterface::class);
        $request->expects($this->any())->method('withAttribute')->willReturn($request);

        $this->handler->addMiddleware($middleware);
        $this->handler->handle($request);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws NotFoundException
     */
    public function testAddMiddlewareWithClassName(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $this->router->expects($this->once())->method('process')
            ->with($request, $this->handler)
            ->willReturnCallback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                return $handler->handle($request);
            });

        $this->container->expects($this->exactly(2))->method('get')
            ->willReturnCallback(function (string $id) use ($request) {
                if (RequestHandlerInterface::class === $id) {
                    return $this->createMock(RequestHandlerInterface::class);
                }
                if ($id === MiddlewareInterface::class) {
                    $middleware = $this->createMock(MiddlewareInterface::class);
                    $middleware->expects($this->once())->method('process')
                        ->willReturnCallback(function (
                            ServerRequestInterface  $request,
                            RequestHandlerInterface $handler
                        ) {
                            return $handler->handle($request);
                        });
                    return $middleware;
                }
                return null;
            });

        $request->expects($this->once())->method('getAttribute')
            ->willReturn(RequestHandlerInterface::class);

        $this->handler->addMiddleware(MiddlewareInterface::class);
        $this->handler->handle($request);
    }
}
