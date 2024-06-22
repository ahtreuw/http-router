<?php declare(strict_types=1);

namespace Http\Test;

use Http\Dispatcher;
use Http\RouterInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouterAddPathTest extends TestCase
{
    public static function addPathProvider(): array
    {
        return [
            ['GET', '/', 'Ctrl'],
            ['POST', '/create', 'Ctrl_2'],
        ];
    }

    /**
     * @dataProvider addPathProvider
     *
     * @throws Exception
     */
    public function testAddPath(string $method, string $path, string $ctrl): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $routerInterface = $this->createMock(RouterInterface::class);

        $router = new class ($routerInterface) implements RouterInterface, MiddlewareInterface {
            public function __construct(private readonly RouterInterface $routerInterface)
            {
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }

            public function addPath(string $method, string $path, string $requestHandler): void
            {
                $this->routerInterface->addPath($method, $path, $requestHandler);
            }
        };

        /** @var RouterInterface&MiddlewareInterface $router */
        $handler = new Dispatcher($container, $router);

        $routerInterface->expects($this->once())
            ->method('addPath')
            ->with($method, $path, $ctrl);

        $handler->addPath($method, $path, $ctrl);
    }

}
