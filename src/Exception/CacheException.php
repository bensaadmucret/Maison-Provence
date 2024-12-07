<?php

namespace App\Exception;

class CacheException extends \RuntimeException
{
    public static function notFound(string $key): self
    {
        return new self(sprintf('Cache key "%s" not found', $key));
    }

    public static function invalidData(string $key, string $expectedType): self
    {
        return new self(sprintf('Invalid data for cache key "%s". Expected %s', $key, $expectedType));
    }

    public static function saveFailed(string $key, string $reason): self
    {
        return new self(sprintf('Failed to save cache for key "%s": %s', $key, $reason));
    }
}
