<?php

namespace LearnAcademy\App;

class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        // Convert :param to named capture groups
        $regex = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        $this->routes[] = compact('method', 'pattern', 'regex', 'handler');
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = strtok($uri, '?');

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) continue;
            if (!preg_match($route['regex'], $uri, $matches)) continue;

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            ($route['handler'])($params);
            return;
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}
