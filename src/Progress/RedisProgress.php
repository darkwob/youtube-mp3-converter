<?php

namespace Darkwob\YoutubeMp3Converter\Progress;

use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;
use Darkwob\YoutubeMp3Converter\Progress\Exceptions\ProgressException;

/**
 * Redis-based progress tracking implementation
 * Supports both PhpRedis and Predis clients
 */
class RedisProgress implements ProgressInterface
{
    /** @var \Redis|\Predis\Client */
    private $redis;
    private string $prefix;
    private int $defaultTtl;

    /**
     * @param \Redis|\Predis\Client $redis Redis client instance
     * @param string $prefix Key prefix for Redis storage
     * @param int $defaultTtl Default TTL for progress records
     * @throws ProgressException
     */
    public function __construct(
        $redis,
        string $prefix = 'progress:',
        int $defaultTtl = 3600
    ) {
        if (!($redis instanceof \Redis) && !($redis instanceof \Predis\Client)) {
            throw new ProgressException('Redis client must be an instance of Redis or Predis\Client');
        }

        $this->redis = $redis;
        $this->prefix = $prefix;
        $this->defaultTtl = $defaultTtl;

        // Test connection
        try {
            $this->redis->ping();
        } catch (\Exception $e) {
            throw new ProgressException('Could not connect to Redis server: ' . $e->getMessage());
        }
    }

    public function update(string $id, string $status, float $progress, string $message): void
    {
        $data = [
            'id' => $id,
            'status' => $status,
            'progress' => $progress,
            'message' => $message,
            'updated_at' => time()
        ];

        $key = $this->getKey($id);
        try {
            $result = $this->redis->setex($key, $this->defaultTtl, json_encode($data, JSON_THROW_ON_ERROR));
            if (!$result) {
                throw new ProgressException("Failed to write progress to Redis: $key");
            }
        } catch (\JsonException $e) {
            throw new ProgressException("Failed to encode progress data: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new ProgressException("Redis error: " . $e->getMessage());
        }
    }

    public function get(string $id): ?array
    {
        try {
            $key = $this->getKey($id);
            $data = $this->redis->get($key);
            
            if ($data === false || $data === null) {
                return null;
            }

            return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ProgressException("Failed to decode progress data: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new ProgressException("Redis error: " . $e->getMessage());
        }
    }

    public function delete(string $id): void
    {
        try {
            $key = $this->getKey($id);
            $this->redis->del($key);
        } catch (\Exception $e) {
            throw new ProgressException("Failed to delete progress: " . $e->getMessage());
        }
    }

    public function getAll(): array
    {
        try {
            $pattern = $this->getKey('*');
            $keys = $this->redis->keys($pattern);
            
            if (!is_array($keys)) {
                return [];
            }

            $progress = [];
            foreach ($keys as $key) {
                $data = $this->redis->get($key);
                if ($data !== false && $data !== null) {
                    $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
                    if ($decoded !== null) {
                        $progress[] = $decoded;
                    }
                }
            }

            return $progress;
        } catch (\JsonException $e) {
            throw new ProgressException("Failed to decode progress data: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new ProgressException("Redis error: " . $e->getMessage());
        }
    }

    public function cleanup(int $maxAge = 3600): void
    {
        try {
            $now = time();
            $pattern = $this->getKey('*');
            $keys = $this->redis->keys($pattern);

            if (!is_array($keys)) {
                return;
            }

            foreach ($keys as $key) {
                $data = $this->redis->get($key);
                if ($data === false || $data === null) {
                    continue;
                }

                $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
                if ($decoded === null) {
                    continue;
                }

                $age = $now - ($decoded['updated_at'] ?? 0);
                if ($age > $maxAge) {
                    $this->redis->del($key);
                }
            }
        } catch (\JsonException $e) {
            throw new ProgressException("Failed to decode progress data: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new ProgressException("Redis error: " . $e->getMessage());
        }
    }

    private function getKey(string $id): string
    {
        return $this->prefix . $id;
    }
} 