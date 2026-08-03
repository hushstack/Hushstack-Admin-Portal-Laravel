<?php

namespace App\Services\Messenger;

use App\Exceptions\Messenger\MessengerApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessengerApiClient
{
    /**
     * Fetch the paginated user list from the Messenger service.
     *
     * The payload is returned untouched so the Admin Portal UI receives the
     * exact pagination envelope the Messenger API produced.
     */
    public function users(array $query = []): array
    {
        return $this->get('/api/internal/users', $query);
    }

    private function get(string $path, array $query = []): array
    {
        return $this->request($path, $query)->json() ?? [];
    }

    private function request(string $path, array $query = []): Response
    {
        $baseUrl = config('services.messenger.url');
        $internalKey = config('services.messenger.internal_key');

        if (blank($baseUrl) || blank($internalKey)) {
            Log::error('Messenger API is not configured.', [
                'has_url' => filled($baseUrl),
                'has_internal_key' => filled($internalKey),
            ]);

            throw new MessengerApiException('Messenger service is not available right now.', 503);
        }

        try {
            $response = Http::baseUrl(rtrim($baseUrl, '/'))
                ->acceptJson()
                ->withHeaders(['X-Internal-Key' => $internalKey])
                ->connectTimeout((int) config('services.messenger.connect_timeout', 5))
                ->timeout((int) config('services.messenger.timeout', 10))
                ->retry(
                    times: 2,
                    sleepMilliseconds: 250,
                    when: fn ($exception) => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError()),
                    throw: false
                )
                ->get($path, $query);
        } catch (ConnectionException $exception) {
            Log::warning('Messenger API is unreachable.', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            throw new MessengerApiException('Messenger service is not available right now.', 503);
        }

        if ($response->failed()) {
            $this->throwMessengerException($response, $path);
        }

        return $response;
    }

    private function throwMessengerException(Response $response, string $path): void
    {
        Log::warning('Messenger API request failed.', [
            'path' => $path,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        // 401/403 means our internal key is wrong or revoked - that is our
        // misconfiguration, not the admin's, so it must not leak downstream.
        $status = match (true) {
            $response->serverError() => 503,
            in_array($response->status(), [401, 403], true) => 503,
            default => $response->status(),
        };

        $message = $status === 503
            ? 'Messenger service is not available right now.'
            : ($response->json('message') ?? 'Messenger request failed.');

        throw new MessengerApiException($message, $status);
    }
}
