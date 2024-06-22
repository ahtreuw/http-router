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

## Path variants

```php
/**
 * @var Psr\Container\ContainerInterface                         $container
 * @var Psr\Http\Server\MiddlewareInterface&Http\RouterInterface $router 
 */
$dispatcher = new Http\Dispatcher(container: $container);

$dispatcher->add(
    method: 'CLI', // works with command line
    path: '/{id:number}/{status:(active|false)}', 
    requestHandler: 'MyRequestHandlerOrController'
);

$dispatcher->add(
    method: 'GET', // works with GET method
    path: '/{id:number}/{status:(active|false)}', 
    requestHandler: 'MyRequestHandlerOrController::details'
);

// composer require vulpes/http
$request = (new Http\Factory\ServerRequestFactory)->createServerRequestFromGlobals();

$response = $dispatcher->handle($request);

```