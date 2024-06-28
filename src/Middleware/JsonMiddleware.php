<?php declare(strict_types=1);

namespace Http\Middleware;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

readonly class JsonMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger,
        private bool            $associative = true,
        private int             $depth = 512,
        private int             $flags = JSON_THROW_ON_ERROR
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (str_contains($request->getHeaderLine('Content-Type'), 'application/json')) {
            if ($body = (string)$request->getBody()) {
                try {
                    $contents = json_decode($body, $this->associative, $this->depth, $this->flags);
                } catch (JsonException $e) {
                    $this->logger->error($e->getMessage(), ['exception' => $e]);
                    $contents = [];
                }
            } else {
                $contents = [];
            }
            $request = $request->withParsedBody($contents);
        }

        $response = $handler->handle($request);

        if ($response->hasHeader('Content-Type') === false) {
            $response = $response->withHeader('Content-Type', 'application/json;charset=utf-8');
        }

        return $response;
    }
}
