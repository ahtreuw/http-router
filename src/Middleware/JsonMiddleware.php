<?php declare(strict_types=1);

namespace Http\Middleware;

use Http\Exception\BadRequestException;
use Http\Exception\RequestException;
use Http\Factory\ResponseFactory;
use Http\Factory\ResponseFactoryInterface;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

readonly class JsonMiddleware implements MiddlewareInterface
{
    private const UNPROCESSABLE_ENTITY = 422;

    public function __construct(
        private ResponseFactoryInterface $responseFactory = new ResponseFactory,
        private LoggerInterface          $logger = new NullLogger,
        private bool                     $associative = true,
        private int                      $depth = 512,
        private int                      $flags = JSON_THROW_ON_ERROR
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {

            $response = $handler->handle($this->parseRequestBody($request));

        } catch (JsonException $exception) {

            $this->logger->error($exception->getMessage(), [
                'error' => $exception,
                'request.body' => $request->getBody()->__toString()
            ]);

            $response = $this->responseFactory
                ->createJson(self::UNPROCESSABLE_ENTITY, ['message' => $exception->getMessage()]);

        } catch (RequestException $exception) {

            $response = $this->responseFactory
                ->createJson($exception::STATUS_CODE, ['message' => $exception->getMessage()]);

        } catch (Throwable $exception) {

            $this->logger->error($exception->getMessage(), [
                'error' => $exception
            ]);

            $response = $this->responseFactory
                ->createJson(BadRequestException::STATUS_CODE, ['message' => $exception->getMessage()]);
        }

        if ($response->hasHeader('Content-Type') === false) {
            $response = $response->withHeader('Content-Type', 'application/json;charset=utf-8');
        }

        return $response;
    }

    /**
     * @throws JsonException
     */
    private function parseRequestBody(ServerRequestInterface $request): ServerRequestInterface
    {
        if (str_contains($request->getHeaderLine('Content-Type'), 'application/json') === false) {
            return $request;
        }
        if ($body = $request->getBody()->__toString()) {
            return $request->withParsedBody(
                json_decode($body, $this->associative, $this->depth, $this->flags | JSON_THROW_ON_ERROR)
            );
        }
        return $request->withParsedBody([]);
    }
}
