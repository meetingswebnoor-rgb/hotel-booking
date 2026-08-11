<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\App;
use App\Core\Request;
use App\Core\Router;

$router = new Router();
(require BASE_PATH . '/routes/web.php')($router);
App::setRouter($router);

$request = Request::capture();
$response = $router->dispatch($request);
$response->send();
