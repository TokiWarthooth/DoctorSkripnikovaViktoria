<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiController
{
    public function __construct(
        private readonly string $rootDir,
    ) {
    }

    /**
     * @param array<string, string> $vars
     */
    public function status(Request $request, array $vars): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'timestamp' => time(),
            'version' => '1.0.0',
        ]);
    }

    /**
     * @param array<string, string> $vars
     */
    public function contact(Request $request, array $vars): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return new JsonResponse(['success' => false, 'error' => 'Некорректное тело запроса'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if ($name === '' || $phone === '' || $message === '') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Укажите имя, телефон и текст обращения',
            ], Response::HTTP_BAD_REQUEST);
        }

        $line = json_encode([
            'received_at' => date('c'),
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE) . "\n";

        $storageDir = $this->rootDir . '/storage';
        if (!is_dir($storageDir) && !mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
            return new JsonResponse(['success' => false, 'error' => 'Не удалось сохранить заявку'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        file_put_contents($storageDir . '/leads.jsonl', $line, FILE_APPEND | LOCK_EX);

        return new JsonResponse([
            'success' => true,
            'message' => 'Заявка принята. Я свяжусь с вами в ближайшее время.',
        ]);
    }
}
