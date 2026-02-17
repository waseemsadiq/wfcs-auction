<?php
declare(strict_types=1);

namespace Core;

/**
 * Simple router for the auction app.
 *
 * Supports GET, POST, DELETE routes with :param placeholders.
 * Param values are passed as ordered positional arguments to the handler.
 *
 * Usage:
 *   $router->get('/auctions/:slug', [$controller, 'show']);
 *   $router->post('/bids', [$controller, 'store']);
 *   $router->dispatch($method, $path);
 */
class Router
{
    /** @var array<int, array{method: string, pattern: string, params: string[], handler: callable}> */
    private array $routes = [];

    /** @var callable|null */
    private mixed $notFoundHandler = null;

    // -------------------------------------------------------------------------
    // Route registration
    // -------------------------------------------------------------------------

    public function get(string $path, mixed $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function delete(string $path, mixed $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    // -------------------------------------------------------------------------
    // Dispatch
    // -------------------------------------------------------------------------

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // $matches[0] is the full match; drop it
                array_shift($matches);

                // Build named param map for the handler (pass as ordered args)
                call_user_func_array($route['handler'], $matches);
                return;
            }
        }

        // No route matched — 404
        $this->handleNotFound();
    }

    // -------------------------------------------------------------------------
    // 404 handler
    // -------------------------------------------------------------------------

    public function notFound(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function addRoute(string $method, string $path, mixed $handler): void
    {
        [$pattern, $params] = $this->compile($path);
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $pattern,
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    /**
     * Convert a path like /auctions/:slug/edit into a regex pattern.
     * Returns [pattern, paramNames].
     */
    private function compile(string $path): array
    {
        $params  = [];
        $pattern = preg_replace_callback(
            '#:([a-zA-Z_][a-zA-Z0-9_]*)#',
            function (array $m) use (&$params): string {
                $params[] = $m[1];
                return '([a-zA-Z0-9_-]+)';
            },
            preg_quote($path, '#')
        );

        // preg_quote escapes ':', but our callback already handled those.
        // We need to un-escape the colons that were in the original path segments
        // — actually, preg_quote runs first on the static text, but the :param
        // replacement callback already emits raw regex. The issue is that
        // preg_quote will escape the colon itself before the callback runs on
        // the result. Let's re-approach: quote non-param parts only.
        $pattern = $this->buildPattern($path, $params);

        return [$pattern, $params];
    }

    /**
     * Build a regex from the path, substituting :param with a capture group.
     */
    private function buildPattern(string $path, array &$params): string
    {
        $params  = [];
        $escaped = preg_replace_callback(
            '#:([a-zA-Z_][a-zA-Z0-9_]*)#',
            function (array $m) use (&$params): string {
                $params[] = $m[1];
                return "\x00PARAM\x00"; // placeholder
            },
            $path
        );

        // Escape everything else for use in a regex
        $escaped = preg_quote($escaped, '#');

        // Restore param placeholders as capture groups
        $pattern = str_replace(preg_quote("\x00PARAM\x00", '#'), '([a-zA-Z0-9_-]+)', $escaped);

        return '#^' . $pattern . '$#';
    }

    private function handleNotFound(): void
    {
        if ($this->notFoundHandler !== null) {
            call_user_func($this->notFoundHandler);
        } else {
            http_response_code(404);
            global $basePath;
            $errorView = dirname(__DIR__) . '/app/Views/errors/404.php';
            if (file_exists($errorView)) {
                require $errorView;
            } else {
                echo 'Not Found';
            }
        }
    }
}
