<?php

namespace App\Jobs\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendRoleAssignmentAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $token || ! $chatId) {
            return;
        }

        // Simple per-day cap to avoid hitting Telegram limits
        $limitPerDay = 1000;
        $counterKey = 'role_assignment_alerts:'.now()->toDateString();
        $count = Cache::increment($counterKey);
        if ($count === 1) {
            Cache::put($counterKey, $count, now()->endOfDay());
        }
        if ($count > $limitPerDay) {
            Log::warning('Telegram role alert skipped: daily cap reached', ['count' => $count]);

            return;
        }

        $threadId = (int) config('contact.role_assignment_thread_id', 109);
        $dateOnly = now()->format('d M Y, h:i A');

        $admin = $this->escape($this->payload['admin'] ?? '-');
        $user = $this->escape($this->payload['user'] ?? '-');
        $role = $this->escape($this->payload['role'] ?? '-');

        $text =
            "🚨 *Admin Role Change*\n"
            ."━━━━━━━━━━━━━━━━━━\n"
            ."👤 *Changed By:* {$admin}\n"
            ."🧑 *User:* {$user}\n"
            ."🔑 *Role Set To:* {$role}\n"
            ."⏰ *When:* {$this->escape($dateOnly)}\n"
            ."━━━━━━━━━━━━━━━━━━\n"
            .'_If this was not expected, review recent admin activity._';

        $response = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'message_thread_id' => $threadId,
            'text' => $text,
            // Markdown is more forgiving than MarkdownV2 for punctuation in static strings.
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
        ]);

        if ($response->failed()) {
            Log::error('Telegram role alert failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    private function escape(string $text): string
    {
        $escapeChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach ($escapeChars as $char) {
            $text = str_replace($char, '\\'.$char, $text);
        }

        return $text;
    }
}
