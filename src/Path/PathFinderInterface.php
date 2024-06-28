<?php declare(strict_types=1);

namespace Http\Path;

use Psr\Http\Message\ServerRequestInterface;

interface PathFinderInterface
{
    public function find(
        ServerRequestInterface $request,
        array                  $simplePaths,
        array                  $complexPaths,
        string                 $path
    ): ?ServerRequestInterface;
}
