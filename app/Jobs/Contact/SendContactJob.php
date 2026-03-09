<?php

namespace App\Jobs\Contact;

use App\Mail\Contact\AdminContactMail;
use App\Mail\Contact\UserContactConfirmationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public array $payload)
    {
    }

    public function handle(): void
    {
        $adminEmail = config('contact.admin_email');

        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new AdminContactMail($this->payload));
        }

        // 2) Email user confirmation
        Mail::to($this->payload['email'])->queue(new UserContactConfirmationMail($this->payload));

        // 3) Telegram
        $this->sendTelegram($this->payload);
    }

    private function sendTelegram(array $payload): void
    {
        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (!$token || !$chatId) {
            return;
        }

        $threadId = (int) config('contact.telegram_thread_id', 93);

        $dateOnly = now()->format('d M Y, h:i A');

        // If you prefer to use submitted_at from payload (if you stored it):
        // $dateOnly = \Carbon\Carbon::parse($payload['submitted_at'] ?? now())->format('d M Y');

        $name  = $this->escapeTelegram($payload['name'] ?? '-');
        $email = $this->escapeTelegram($payload['email'] ?? '-');

        $message = trim((string) ($payload['message'] ?? ''));
        $message = $message === '' ? '-' : $this->escapeTelegram($message);

        $text =
            "📩 *New Contact Message*\n"
            . "━━━━━━━━━━━━━━━━━━\n"
            . "*From:* {$name}\n"
            . "*Email:* {$email}\n"
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

    /**
     * Escape text for MarkdownV2
     */
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
        // Optional: log if you want
        // logger()->error('Contact job failed', ['error' => $e->getMessage(), 'payload' => $this->payload]);
    }
}
