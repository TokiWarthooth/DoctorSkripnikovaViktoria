<?php

declare(strict_types=1);

namespace App;

use App\Controller\ApiController;
use App\Controller\PageController;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class Application
{
    public function __construct(
        private readonly string $rootDir,
        private readonly Environment $twig,
    ) {
    }

    public static function create(string $rootDir): self
    {
        $loader = new FilesystemLoader($rootDir . '/templates');
        $twig = new Environment($loader, [
            'cache' => false,
            'strict_variables' => true,
        ]);

        return new self($rootDir, $twig);
    }

    public function handle(Request $request): Response
    {
        $page = new PageController($this->twig);
        $api = new ApiController($this->rootDir);

        $dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) use ($page, $api): void {
            $r->addRoute('GET', '/', [$page, 'index']);
            $r->addRoute('GET', '/about', [$page, 'about']);
            $r->addRoute('GET', '/prices', [$page, 'prices']);
            $r->addRoute('GET', '/contact', [$page, 'contact']);
            $r->addRoute('GET', '/api/status', [$api, 'status']);
            $r->addRoute('POST', '/api/contact', [$api, 'contact']);
        });

        $routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());

        return match ($routeInfo[0]) {
            Dispatcher::NOT_FOUND => new Response('Страница не найдена', 404, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]),
            Dispatcher::METHOD_NOT_ALLOWED => new Response('Метод не поддерживается', 405, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]),
            Dispatcher::FOUND => $this->invokeHandler($routeInfo[1], $request, $routeInfo[2]),
            default => new Response('Ошибка маршрутизации', 500),
        };
    }

    /**
     * @param callable(Request, array<string, string>): Response $handler
     * @param array<string, string> $vars
     */
    private function invokeHandler(callable $handler, Request $request, array $vars): Response
    {
        return $handler($request, $vars);
    }
}
