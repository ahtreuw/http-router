# Simple router & dispatcher implementation.

## Available methods
* **ANY** available to all methods
* **CLI** used on command line prompt
* **GET** method requests a representation of the specified resource.
* **HEAD** method asks for a response identical to a GET request, but without the response body.
* **POST** method submits an entity to the specified resource, often causing a change in state or side effects on the server.
* **PUT** method replaces all current representations of the target resource with the request payload.
* **DELETE** method deletes the specified resource.
* **CONNECT** method establishes a tunnel to the server identified by the target resource.
* **OPTIONS** method describes the communication options for the target resource.
* **TRACE** method performs a message loop-back test along the path to the target resource.
* **PATCH** method applies partial modifications to a resource.

## Path variants, and middlewares

```php
<?php declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

require __DIR__ . '/vendor/autoload.php';

// composer require vulpes/container
$container = new Container\Container;

/** @var Psr\Container\ContainerInterface $container */
$dispatcher = new Http\Dispatcher(container: $container);

// add anonymous Middleware globally as object
$dispatcher->addMiddleware(new class implements MiddlewareInterface {
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // TODO before
        $response = $handler->handle($request);
        // TODO after
        return $response;
    }
});

// add Middleware globally as string (the ContainerInterface will create it's instance)
$dispatcher->addMiddleware(Middleware\AnotherMiddleware::class);

// You can also insert middleware at the very beginning of the line, for example for error handling,
// to catch the Http\Exception\NotFoundException thrown by the router
$dispatcher->appendMiddleware(Middleware\ErrorHandlerMiddleware::class);

$dispatcher
    // add command line endpoint MyController->index()
    ->addPath(method: 'GET', path: '/', requestHandler: MyController::class . '::index')
    // add Middleware locally to current Path as string (classname), you can use object as well
    ->addMiddleware(Http\Middleware\JsonMiddleware::class)

    // add command line endpoint, MyController->service(int $thread)
    ->addPath(method: 'CLI', path: '/service/{thread:int}', requestHandler: MyController::class . '::service')

    // add command line endpoint, MyController->create()
    ->addPath(method: 'POST', path: '/entity/create', requestHandler: MyController::class . '::create')
    // add Middleware locally to current path as string (classname), you can use object as well
    ->addMiddleware(Middleware\MyMiddleware::class)
    ->appendMiddleware(Middleware\AnotherErrorMiddleware::class)
    // The above middlewares will behave according to the pattern below (with global middlewares)
    // AnotherErrorMiddleware  :before
    // ErrorHandlerMiddleware  :before
    // RouterInterface         :before - added on construct Dispatcher, throws NotFoundException
    // anonymous               :before
    // AnotherMiddleware       :before
    // MyMiddleware            :before
    // MyController->create()          - throws Throwable, everything from controller
    // MyMiddleware            :after
    // AnotherMiddleware       :after
    // anonymous               :after
    // RouterInterface         :after - Nothing happens here anymore
    // ErrorHandlerMiddleware  :after - You can catch RouterInterface's NotFoundException here
    // AnotherErrorMiddleware  :after - ... or here

    // You can use RequestHandler (RequestHandlerInterface) as Controller too
    ->addPath(method: 'POST', path: '/entity/create', requestHandler: MyRequestHandler::class);

// composer require vulpes/http
$serverRequest = (new Http\Factory\ServerRequestFactory)->createServerRequestFromGlobals();

try {
    print $dispatcher->handle($serverRequest)/* ResponseInterface */->getBody();
} catch (Throwable $e) {
    print $e;
}
```