<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FirebaseOptimizer
{
    /**
     * Guard the application against excessive Firebase calls on shared hosting.
     */
    public function handle(Request $request, Closure $next)
    {
        $settings = config('optimization.firebase', []);
        $rateLimit = max(1, (int) ($settings['rate_limit_per_minute'] ?? 3));
        $cacheTtl = now()->addMinute();

        $cacheKey = sprintf('firebase:ops:%s', $request->ip());
        $currentCount = Cache::get($cacheKey, 0);

        if ($currentCount >= $rateLimit) {
            Log::warning('Firebase operations throttled to protect shared resources.', [
                'ip' => $request->ip(),
                'rate_limit' => $rateLimit,
            ]);

            // Back off briefly; callers should already be falling back to MySQL.
            usleep(200000); // 200ms
        } else {
            Cache::put($cacheKey, $currentCount + 1, $cacheTtl);
        }

        try {
            return $next($request);
        } finally {
            if (Cache::has($cacheKey)) {
                $remaining = Cache::decrement($cacheKey);
                if ($remaining <= 0) {
                    Cache::forget($cacheKey);
                }
            }
        }
    }
}

