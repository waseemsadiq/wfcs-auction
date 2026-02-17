<?php
declare(strict_types=1);

namespace Core;

class Controller
{
    /**
     * Render a view file, extracting $data into scope.
     * $basePath and $csrfToken are made available from global scope.
     */
    protected function view(string $path, array $data = []): void
    {
        extract($data);
        // $basePath and $csrfToken are globals set by index.php
        global $basePath, $csrfToken;
        require dirname(__DIR__) . '/app/Views/' . $path . '.php';
    }

    /**
     * Render an inner view file into a string, with $data variables extracted into scope.
     */
    protected function renderView(string $path, array $data = []): string
    {
        extract($data);
        global $basePath, $csrfToken;
        ob_start();
        require dirname(__DIR__) . '/app/Views/' . $path . '.php';
        return (string) ob_get_clean();
    }

    /**
     * Redirect to a URL and halt execution.
     */
    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Emit a JSON response and halt execution.
     */
    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Render an HTTP error page and halt execution.
     */
    protected function abort(int $code): never
    {
        global $basePath;
        http_response_code($code);
        $errorView = dirname(__DIR__) . '/app/Views/errors/' . $code . '.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "HTTP $code";
        }
        exit;
    }
}
