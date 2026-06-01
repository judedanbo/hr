<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogApiRequests
{
    private const START_ATTRIBUTE = 'api_log_started_at';

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::START_ATTRIBUTE, microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $start = $request->attributes->get(self::START_ATTRIBUTE);
            $durationMs = $start !== null ? (int) round((microtime(true) - $start) * 1000) : null;

            ApiLog::create([
                'method' => $request->getMethod(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'user_id' => $request->user()?->getKey(),
                'token_name' => $this->tokenName($request),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'duration_ms' => $durationMs,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function tokenName(Request $request): ?string
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token->name : null;
    }
}
