<?php declare(strict_types=1);

namespace Http\Test;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface TestMockInterface
{
    public function setServerRequest(ServerRequestInterface $serverRequest): void;

    public function index(int $id = null): ResponseInterface;

    public function details(int $id): ResponseInterface;

    public function customMethod(int $id, string $message, float $number): ResponseInterface;
}