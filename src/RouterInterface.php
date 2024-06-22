<?php declare(strict_types=1);

namespace Http;

interface RouterInterface
{
    /**
     * The GET method requests a representation of the specified resource.
     * Requests using GET should only retrieve data.
     */
    public const GET = 'GET';

    /**
     * The HEAD method asks for a response identical to a GET request, but without the response body.
     */
    public const HEAD = 'HEAD';

    /**
     * The POST method submits an entity to the specified resource, often causing a change in state or side effects on the server.
     */
    public const POST = 'POST';

    /**
     * The PUT method replaces all current representations of the target resource with the request payload.
     */
    public const PUT = 'PUT';

    /**
     * The DELETE method deletes the specified resource.
     */
    public const DELETE = 'DELETE';

    /**
     * The CONNECT method establishes a tunnel to the server identified by the target resource.
     */
    public const CONNECT = 'CONNECT';

    /**
     * The OPTIONS method describes the communication options for the target resource.
     */
    public const OPTIONS = 'OPTIONS';

    /**
     * The TRACE method performs a message loop-back test along the path to the target resource.
     */
    public const TRACE = 'TRACE';

    /**
     * The PATCH method applies partial modifications to a resource.
     */
    public const PATCH = 'PATCH';

    /**
     * The CLI used on command line prompt
     */
    const CLI = 'CLI';

    /**
     * Available to all methods
     */
    const ANY = 'ANY';

    /**
     * Add route path to router
     */
    public function addPath(string $method, string $path, string $requestHandler): void;
}
