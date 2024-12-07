<?php

namespace App\Service\Cache;

use App\Exception\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheService
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     *
     * @throws CacheException
     */
    public function get(string $key, callable $callback, int $expiresAfter = 3600)
    {
        try {
            return $this->cache->get($key, function (ItemInterface $item) use ($callback, $expiresAfter, $key) {
                $item->expiresAfter($expiresAfter);

                try {
                    $result = $callback();

                    $this->logger->info('Cache item generated', [
                        'key' => $key,
                        'expires_after' => $expiresAfter,
                    ]);

                    return $result;
                } catch (\Throwable $e) {
                    $this->logger->error('Failed to generate cache item', [
                        'key' => $key,
                        'error' => $e->getMessage(),
                    ]);
                    throw CacheException::saveFailed($key, $e->getMessage());
                }
            });
        } catch (\Throwable $e) {
            $this->logger->error('Cache error', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            throw CacheException::notFound($key);
        }
    }

    public function delete(string $key): void
    {
        try {
            $this->cache->deleteItem($key);
            $this->logger->info('Cache item deleted', ['key' => $key]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete cache item', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function clear(): void
    {
        try {
            $this->cache->clear();
            $this->logger->info('Cache cleared');
        } catch (\Throwable $e) {
            $this->logger->error('Failed to clear cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
