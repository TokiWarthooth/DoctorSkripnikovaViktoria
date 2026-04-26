<?php

declare(strict_types=1);

use App\Application;
use App\Env;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
Env::load($root);
$app = Application::create($root);
$request = Request::createFromGlobals();
$response = $app->handle($request);
$response->send();
