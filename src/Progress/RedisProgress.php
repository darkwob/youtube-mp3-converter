<?php

namespace Darkwob\YoutubeMp3Converter\Progress;

use Darkwob\YoutubeMp3Converter\Progress\Interfaces\ProgressInterface;
use \Redis;

class RedisProgress implements ProgressInterface
{
    private Redis $redis;
    private string $prefix;
    private int $defaultTtl;

    public function __construct(
        Redis $redis,
        string $prefix = 'progress:',
        int $defaultTtl = 3600
    ) {
        $this->redis = $redis;
        $this->prefix = $prefix;
        $this->defaultTtl = $defaultTtl;
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
        if (!$this->redis->setex($key, $this->defaultTtl, json_encode($data))) {
            throw new \RuntimeException("Cannot write progress to Redis: $key");
        }
    }

    public function get(string $id): ?array
    {
        $key = $this->getKey($id);
        $data = $this->redis->get($key);
        
        if ($data === false) {
            return null;
        }

        $decoded = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    public function delete(string $id): void
    {
        $key = $this->getKey($id);
        $this->redis->del($key);
    }

    public function getAll(): array
    {
        $pattern = $this->getKey('*');
        $keys = $this->redis->keys($pattern);
        
        $progress = [];
        foreach ($keys as $key) {
            $data = $this->redis->get($key);
            if ($data !== false) {
                $decoded = json_decode($data, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $progress[] = $decoded;
                }
            }
        }

        return $progress;
    }

    public function cleanup(int $maxAge = 3600): void
    {
        $now = time();
        $pattern = $this->getKey('*');
        $keys = $this->redis->keys($pattern);

        foreach ($keys as $key) {
            $data = $this->redis->get($key);
            if ($data === false) {
                continue;
            }

            $decoded = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            $age = $now - ($decoded['updated_at'] ?? 0);
            if ($age > $maxAge) {
                $this->redis->del($key);
            }
        }
    }

    private function getKey(string $id): string
    {
        return $this->prefix . $id;
    }
} 