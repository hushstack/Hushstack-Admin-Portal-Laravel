<?php

namespace App\Jobs\Member;

use App\Mail\Member\AdminMemberRequestMail;
use App\Mail\Member\UserMemberRequestConfirmationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendMemberRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public array $payload)
    {
    }

    public function handle(): void
    {
        $adminEmail = config('contact.admin_email', 'hushstack168@gmail.com');

        // Admin mail
        Mail::to($adminEmail)->queue(new AdminMemberRequestMail($this->payload));

        // User confirmation
        Mail::to($this->payload['email'])->queue(new UserMemberRequestConfirmationMail($this->payload));

        // Telegram
        $this->sendTelegram($this->payload);
    }

    private function sendTelegram(array $payload): void
    {
        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (!$token || !$chatId) {
            return;
        }

        $threadId = (int) config('contact.member_telegram_thread_id', 99);
        $dateOnly = now()->format('d M Y, h:i A');

        $name  = $this->escapeTelegram($payload['name'] ?? '-');
        $email = $this->escapeTelegram($payload['email'] ?? '-');
        $telegram = $this->escapeTelegram($payload['telegram_number'] ?? '-');

        $message = trim((string) ($payload['message'] ?? ''));
        $message = $message === '' ? '-' : $this->escapeTelegram($message);

        $text =
            "🧑‍🤝‍🧑 *New Member Request*\n"
            . "━━━━━━━━━━━━━━━━━━\n"
            . "*From:* {$name}\n"
            . "*Email:* {$email}\n"
            . "*Telegram:* {$telegram}\n"
            . "*Date:* {$this->escapeTelegram($dateOnly)}\n"
            . "━━━━━━━━━━━━━━━━━━\n"
            . "*Message*\n"
            . "{$message}\n";

        Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'message_thread_id' => $threadId,
            'text' => $text,
            'parse_mode' => 'MarkdownV2',
            'disable_web_page_preview' => true,
        ]);
    }

    private function escapeTelegram(string $text): string
    {
        $escapeChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach ($escapeChars as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }
        return $text;
    }

    public function failed(Throwable $e): void
    {
        // logger()->error('Member request job failed', ['error' => $e->getMessage(), 'payload' => $this->payload]);
    }
}
