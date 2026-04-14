<?php

declare(strict_types=1);

use App\Application;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = Application::create(dirname(__DIR__));
$request = Request::createFromGlobals();
$response = $app->handle($request);
$response->send();
