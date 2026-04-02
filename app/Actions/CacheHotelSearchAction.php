<?php

namespace App\Actions;

use App\DTOs\SearchParamsDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CacheHotelSearchAction
{
    private const int MAX_CACHED = 10;
    private const string CACHE_KEYS_TRACKER = 'hotel_search:keys';

    public function __construct(
        private readonly SearchHotelsAction $searchAction,
    ) {}

    public function __invoke(SearchParamsDTO $params): Collection
    {
        $cacheKey = $params->cacheKey();

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($params, $cacheKey) {
            $results = ($this->searchAction)($params);
            $this->trackCacheKey($cacheKey);
            return $results;
        });
    }

    private function trackCacheKey(string $cacheKey): void
    {
        $trackedKeys = Cache::get(self::CACHE_KEYS_TRACKER, []);

        if (!in_array($cacheKey, $trackedKeys, true)) {
            $trackedKeys[] = $cacheKey;

            while (count($trackedKeys) > self::MAX_CACHED) {
                $evictKey = array_shift($trackedKeys);
                Cache::forget($evictKey);
            }

            Cache::put(self::CACHE_KEYS_TRACKER, $trackedKeys);
        }
    }
}
