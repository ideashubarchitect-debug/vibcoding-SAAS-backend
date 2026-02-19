<?php

declare(strict_types=1);

namespace Vibeable\Backend\Router;

class Router
{
    /** @var array<string, array<string, array{handler: callable, middleware?: array<string>}>> */
    private array $routes = [];

    public function get(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable $handler, array $middleware = []): self
    {
        return $this->add('DELETE', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, callable $handler, array $middleware = []): self
    {
        $path = $this->normalizePath($path);
        $this->routes[$method][$path] = ['handler' => $handler, 'middleware' => $middleware];
        return $this;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function dispatch(string $method, string $uri)
    {
        $uri = $this->normalizePath($uri);
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $path => $def) {
            $params = $this->match($path, $uri);
            if ($params !== null) {
                $handler = $def['handler'];
                $middleware = $def['middleware'] ?? [];
                $payload = $this->requestPayload();
                $next = fn () => $handler($params, $payload);
                foreach (array_reverse($middleware) as $m) {
                    $next = fn () => $m($params, $next);
                }
                return $next();
            }
        }

        if ($method === 'OPTIONS') {
            return null;
        }

        throw new \Exception('Not Found', 404);
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }

    /**
     * @return array<string, string>|null
     */
    private function match(string $pattern, string $uri): ?array
    {
        $pattern = preg_quote($pattern, '#');
        $pattern = preg_replace('#\\\\\{(\w+)\\\\\}#', '(?P<$1>[^/]+)', $pattern);
        if (preg_match('#^' . $pattern . '$#', $uri, $m)) {
            return array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
        }
        return null;
    }

    private function requestPayload(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') {
            return array_merge($_GET, $_POST);
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? array_merge($_GET, $decoded) : $_GET;
    }
}
