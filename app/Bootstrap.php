<?php

declare(strict_types=1);

namespace Vibeable\Backend;

use Vibeable\Backend\Router\Router;

final class Bootstrap
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function createApp(): App
    {
        $configPath = $this->basePath . '/config';
        $router = new Router();
        require $this->basePath . '/routes/api.php';

        return new App($router, $configPath);
    }
}
