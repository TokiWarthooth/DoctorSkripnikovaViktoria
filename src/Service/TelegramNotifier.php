<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Уведомления о заявках с форм сайта (sendMessage Bot API).
 */
final class TelegramNotifier
{
    /** @var array<string, string> */
    private const SUBJECT_LABELS = [
        'consult' => 'Консультация',
        'inject' => 'Инъекционные процедуры',
        'care' => 'Уход и косметика',
        'other' => 'Другое',
    ];

    private function __construct(
        private readonly ?string $botToken,
        private readonly ?string $chatId,
        private readonly ?string $socks5Proxy,
        private readonly ?string $socks5User,
        private readonly ?string $socks5Pass,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $t = trim((string) (getenv('TELEGRAM_BOT_TOKEN') ?: ''));
        $c = trim((string) (getenv('TELEGRAM_CHAT_ID') ?: ''));
        $px = trim((string) (getenv('TELEGRAM_SOCKS5_PROXY') ?: ''));
        $u = trim((string) (getenv('TELEGRAM_SOCKS5_USER') ?: ''));
        $pRaw = (string) (getenv('TELEGRAM_SOCKS5_PASS') ?? '');
        $p = trim($pRaw) === '' ? null : $pRaw;
        $u = $u === '' ? null : $u;

        return new self(
            $t === '' ? null : $t,
            $c === '' ? null : $c,
            $px === '' ? null : $px,
            $u === '' ? null : $u,
            $p === '' ? null : $p,
        );
    }

    public function isConfigured(): bool
    {
        return $this->botToken !== null && $this->chatId !== null;
    }

    /**
     * @return true если сообщение доставлено, false при ошибке или отсутствии настроек
     */
    public function notifyNewLead(
        string $name,
        string $phone,
        string $email,
        string $subject,
        string $message,
        string $formSource,
    ): bool {
        if (!$this->isConfigured()) {
            return false;
        }

        $text = $this->formatText($name, $phone, $email, $subject, $message, $formSource);

        return $this->sendMessage($text);
    }

    private function formatText(string $name, string $phone, string $email, string $subject, string $message, string $formSource,): string
    {
        $sourceLabel = match ($formSource) {
            'index' => 'Главная — блок «Запись на приём»',
            'contact' => 'Страница «Запись и вопросы»',
            default => 'Форма: ' . ($formSource !== '' ? $formSource : 'сайт'),
        };
        $subjectKey = $subject;
        $subjectLine = $subjectKey !== '' && isset(self::SUBJECT_LABELS[$subjectKey])
            ? self::SUBJECT_LABELS[$subjectKey]
            : ($subjectKey !== '' ? $subjectKey : 'не указана');

        $emailLine = $email !== '' ? $email : '—';

        return implode("\n", [
            'Новая заявка с сайта',
            '',
            'Источник: ' . $sourceLabel,
            'Тема: ' . $subjectLine,
            '',
            'Имя: ' . $name,
            'Телефон: ' . $phone,
            'Email: ' . $emailLine,
            '',
            'Сообщение:',
            $message,
        ]);
    }

    private function sendMessage(string $text): bool
    {
        $url = 'https://api.telegram.org/bot' . $this->botToken . '/sendMessage';
        $body = json_encode([
            'chat_id' => $this->chatId,
            'text' => $text,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($body === false) {
            error_log('Telegram sendMessage: json_encode failed');
            return false;
        }

        if (!\function_exists('curl_init')) {
            error_log('Telegram sendMessage: нужен PHP extension curl (в т.ч. для SOCKS5-прокси).');
            return false;
        }

        return $this->sendWithCurl($url, $body);
    }

    private function sendWithCurl(string $url, string $body): bool
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }

        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
        ];

        if ($this->socks5Proxy !== null) {
            $opts[CURLOPT_PROXY] = $this->socks5Proxy;
            $opts[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5_HOSTNAME;
            if ($this->socks5User !== null && $this->socks5User !== '' && $this->socks5Pass !== null) {
                $opts[CURLOPT_PROXYUSERPWD] = $this->socks5User . ':' . $this->socks5Pass;
            }
        }

        curl_setopt_array($ch, $opts);

        $raw = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $raw === '') {
            error_log('Telegram sendMessage: curl: ' . ($curlErr !== '' ? $curlErr : 'пустой ответ'));
            return false;
        }

        $decoded = json_decode($raw, true);
        if (!\is_array($decoded) || empty($decoded['ok'])) {
            error_log('Telegram sendMessage: ' . $raw);
            return false;
        }

        return true;
    }
}
