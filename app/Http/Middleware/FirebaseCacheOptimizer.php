<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FirebaseCacheOptimizer
{
    /**
     * Ensure Firebase-related cache entries do not explode on shared hosting.
     */
    public function handle(Request $request, Closure $next)
    {
        $cacheConfig = config('optimization.cache', []);
        $maxEntries = (int) ($cacheConfig['firebase_operations_ttl'] ?? 60);

        $cacheKey = 'firebase:cache:tracker';
        $entries = Cache::get($cacheKey, 0);

        if ($entries > $maxEntries) {
            Cache::forget($cacheKey);
            Log::warning('Firebase cache cleared automatically to avoid memory pressure.', [
                'max_entries' => $maxEntries,
            ]);
        }

        Cache::put($cacheKey, min($entries + 1, $maxEntries + 1), now()->addSeconds(10));

        return $next($request);
    }
}

