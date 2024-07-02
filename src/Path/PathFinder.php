<?php declare(strict_types=1);

namespace Http\Path;

use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;

class PathFinder implements PathFinderInterface
{
    private const ALPHANUMERICS = "([a-zA-Z0-9-._~+]+)";
    private const NUMERIC = "([0-9.-]+)";
    private const INTEGER = "([0-9-]+)";
    private const BOOLEAN = "(true|false|t|f|yes|no|y|n|0|1)";
    private const COMPLEX_TYPES = [
        'numeric' => self::NUMERIC,
        'number' => self::NUMERIC,
        'string' => self::ALPHANUMERICS,
        'bool' => self::BOOLEAN,
        'boolean' => self::BOOLEAN,
        'int' => self::INTEGER,
        'integer' => self::INTEGER,
        'float' => self::NUMERIC,
        'double' => self::NUMERIC
    ];

    private array $complexPatterns = [];

    public function __construct(private readonly null|CacheInterface $cache = null)
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @param PathInterface[] $simplePaths
     * @param PathInterface[] $complexPaths
     * @param string $path
     * @return ServerRequestInterface|null
     */
    public function find(
        ServerRequestInterface $request,
        array                  $simplePaths,
        array                  $complexPaths,
        string                 $path
    ): ?ServerRequestInterface
    {
        if (array_key_exists($path, $simplePaths)) {
            return $request
                ->withAttribute(ParametersInterface::class, [])
                ->withAttribute(PathInterface::class, $simplePaths[$path]);
        }

        try {
            $this->prepareComplexPatterns($complexPaths);
        } catch (InvalidArgumentException) {
        }

        foreach ($complexPaths as $complexPath => $pathInstance) {
            if (is_null($params = $this->findRequestHandlerViaComplexPath($complexPath, $path))) {
                continue;
            }
            return $this->createComplexPathRequest($request, $pathInstance, $params);
        }

        return null;
    }

    protected function findRequestHandlerViaComplexPath(string $complexPath, string $path): ?array
    {
        [$pattern, $keys, $types] = $this->createComplexPathPattern($complexPath);

        if (!preg_match($pattern, $path, $matches)) {
            return null;
        }

        array_shift($matches);

        $params = [];

        foreach (array_combine($keys, $matches) ?: [] as $key => $value) {
            $params[$key] = $this->prepareParamValue(array_shift($types), $value);
        }

        return $params;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function prepareComplexPatterns(array $map): void
    {
        if (!$this->cache || $this->complexPatterns = $this->cache->get(self::class, [])) {
            return;
        }
        foreach ($map as $complexPath => $controller) {
            $this->complexPatterns[$complexPath] = $this->createComplexPathPattern($complexPath);
        }
        $this->cache->set(self::class, $this->complexPatterns);
    }

    private function createComplexPathPattern(string $complexPath): array
    {
        if (array_key_exists($complexPath, $this->complexPatterns)) {
            return $this->complexPatterns[$complexPath];
        }

        if (!preg_match_all("/({[()a-z0-9:.-]+})/i", $complexPath, $matches)) {
            throw new RuntimeException('InValid complex path pattern: "' . $complexPath . '"');
        }

        $replace = $types = $keys = [];
        foreach ($matches[0] as $i => $match) {
            [$key, $type] = array_pad(explode(':', trim($match, '{}')), -2, null);
            $keys[$i] = $key ?: $i;
            $types[$i] = $type;
            $replace[$i] = self::COMPLEX_TYPES[$type] ?? $type;
        }

        $complexPath = str_replace('/', '\/', $complexPath);
        $pattern = "/^" . str_replace($matches[0], $replace, $complexPath) . "$/im";

        return [$pattern, $keys, $types];
    }

    public function createComplexPathRequest(
        ServerRequestInterface $request,
        PathInterface          $path,
        array                  $params
    ): ServerRequestInterface
    {
        $assoc = count(array_filter(array_keys($params), 'is_string')) === count($params);

        $request = $request
            ->withAttribute(ParametersInterface::class, $assoc ? $params : array_values($params))
            ->withAttribute(PathInterface::class, $path);

        if ($assoc) {
            foreach ($params as $key => $param) {
                $request = $request->withAttribute($key, $param);
            }
        }

        return $request;
    }

    public function prepareParamValue(null|string $type, string $value): string|int|float|bool|null
    {
        if (!array_key_exists($type, self::COMPLEX_TYPES)) {
            return $value;
        }
        return match ($type) {
            'numeric', 'number', 'float', 'double' => floatval($value),
            'bool', 'boolean' => in_array($value, ['true', 'false', 't', 'f', 'yes', 'no', 'y', 'n', '0', '1']),
            'int', 'integer' => intval($value),
            default => $value
        };
    }
}
