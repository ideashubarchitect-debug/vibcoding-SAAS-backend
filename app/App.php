<?php

declare(strict_types=1);

namespace Vibeable\Backend;

use Vibeable\Backend\Router\Router;

final class App
{
    public function __construct(
        private readonly Router $router,
        private readonly string $configPath
    ) {
    }

    public function run(): void
    {
        $this->sendCorsHeaders();
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        try {
            $result = $this->router->dispatch($method, $requestUri);
            if ($result !== null) {
                $this->json($result);
            }
        } catch (\Throwable $e) {
            $config = require $this->configPath . '/app.php';
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? (int) $e->getCode() : 500;
            http_response_code($code);
            $this->json([
                'error' => true,
                'message' => $config['debug'] ? $e->getMessage() : 'Internal server error',
                ...($config['debug'] ? ['trace' => $e->getTraceAsString()] : []),
            ]);
        }
    }

    private function sendCorsHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Tenant-ID');
    }

    private function json(mixed $data): void
    {
        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
