<?php declare(strict_types=1);

namespace Http\Test;

use Http\ControllerInterface;
use PHPUnit\Framework\MockObject\Exception;
use RuntimeException;
use Http\Path\PathInterface;
use Http\RequestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class RequestHandlerTest extends TestCase
{
    private RequestHandlerInterface $handler;
    private ServerRequestInterface $request;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->handler = new RequestHandler;
        $this->request = $this->createMock(ServerRequestInterface::class);
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function testCallRequestHandler(): void
    {
        $controller = $this->createMock(RequestHandlerInterface::class);

        $this->request->expects($this->once())
            ->method('getAttribute')
            ->with(ControllerInterface::class)
            ->willReturn($controller);

        self::assertInstanceOf(ResponseInterface::class, $this->handler->handle($this->request));
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function testCallIndex(): void
    {
        $controller = $this->createMock(TestMockInterface::class);
        $path = $this->createMock(PathInterface::class);

        $this->request->expects($this->exactly(2))
            ->method('getAttribute')
            ->willReturnOnConsecutiveCalls($controller, $path);

        self::assertInstanceOf(ResponseInterface::class, $this->handler->handle($this->request));
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function testCallDetails(): void
    {
        $controller = $this->createMock(TestMockInterface::class);
        $path = $this->createMock(PathInterface::class);

        $this->request->expects($this->exactly(2))
            ->method('getAttribute')
            ->willReturnOnConsecutiveCalls($controller, $path);

        $path->expects($this->once())->method('getParams')->willReturn([13]);

        self::assertInstanceOf(ResponseInterface::class, $this->handler->handle($this->request));
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function testCallCustomMethod(): void
    {
        $controller = $this->createMock(TestMockInterface::class);
        $path = $this->createMock(PathInterface::class);

        $this->request->expects($this->exactly(2))
            ->method('getAttribute')
            ->willReturnOnConsecutiveCalls($controller, $path);

        $path->expects($this->once())->method('getParams')->willReturn([13, 'a string', 11.42]);
        $path->expects($this->once())->method('getMethodName')->willReturn('customMethod');

        self::assertInstanceOf(ResponseInterface::class, $this->handler->handle($this->request));
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function testCallNotExistingMethod(): void
    {
        $controller = $this->createMock(ControllerInterface::class);
        $path = $this->createMock(PathInterface::class);

        $this->request->expects($this->exactly(2))
            ->method('getAttribute')
            ->willReturnOnConsecutiveCalls($controller, $path);

        self::expectException(RuntimeException::class);
        self::expectExceptionMessage(sprintf("Routing error, %s::%s is not known", get_class($controller), '(N/A)'));

        self::assertInstanceOf(ResponseInterface::class, $this->handler->handle($this->request));
    }
}
