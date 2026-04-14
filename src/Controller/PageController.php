<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

final class PageController
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    /**
     * @param array<string, string> $vars
     */
    public function index(Request $request, array $vars): Response
    {
        return new Response($this->twig->render('index.html.twig', [
            'title' => 'Виктория Скрипникова — врач-дерматолог, косметолог',
        ]));
    }

    /**
     * @param array<string, string> $vars
     */
    public function about(Request $request, array $vars): Response
    {
        return new Response($this->twig->render('about.html.twig', [
            'title' => 'О враче — Виктория Скрипникова',
        ]));
    }

    /**
     * @param array<string, string> $vars
     */
    public function prices(Request $request, array $vars): Response
    {
        return new Response($this->twig->render('prices.html.twig', [
            'title' => 'Прайс — Виктория Скрипникова',
        ]));
    }

    /**
     * @param array<string, string> $vars
     */
    public function contact(Request $request, array $vars): Response
    {
        return new Response($this->twig->render('contact.html.twig', [
            'title' => 'Запись на приём — Виктория Скрипникова',
        ]));
    }
}
