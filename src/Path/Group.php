<?php declare(strict_types=1);

namespace Http\Path;

class Group implements GroupInterface
{
    public function __construct(
        protected readonly string $name,
        protected readonly string $path
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}