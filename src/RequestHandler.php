<?php declare(strict_types=1);

namespace Http;

use Http\Path\PathInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Throwable;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * @throws Throwable
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $controller = $this->getController($request);

        if ($controller instanceof RequestHandlerInterface) {
            return $controller->handle($request);
        }

        if (method_exists($controller, 'setServerRequest')) {
            $controller->setServerRequest($request);
        }

        return $this->process($request, $controller);
    }

    /**
     * @throws Throwable
     */
    private function process(ServerRequestInterface $request, object $controller): ResponseInterface
    {
        $method = ($path = $this->getPath($request))->getMethodName();
        $count = count($params = $path->getParams());

        if (is_null($method) && $count && method_exists($controller, 'details')) {
            return $controller->details(...$params);
        }

        if (is_null($method) && method_exists($controller, 'index')) {
            return $controller->index(...$params);
        }

        if (is_null($method) || method_exists($controller, $method) === false) {
            $format = "Routing error, %s::%s is not known";
            throw new RuntimeException(sprintf($format, get_class($controller), $method ?? '(N/A)'));
        }

        return $controller->$method(...$params);
    }

    private function getController(ServerRequestInterface $request): object
    {
        return $request->getAttribute(ControllerInterface::class);
    }

    private function getPath(ServerRequestInterface $serverRequest): PathInterface
    {
        return $serverRequest->getAttribute(PathInterface::class);
    }
}
