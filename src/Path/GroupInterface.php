<?php declare(strict_types=1);

namespace Http\Path;

interface GroupInterface
{
    public function getName(): string;

    public function getPath(): string;
}