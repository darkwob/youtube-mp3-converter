<?php

namespace Darkwob\YoutubeMp3Converter\Progress\Exceptions;

class ProgressException extends \Exception
{
    public static function connectionFailed(string $message): self
    {
        return new self("Redis connection failed: $message");
    }

    public static function invalidClient(string $type): self
    {
        return new self("Invalid Redis client type: $type");
    }

    public static function writeFailed(string $key, string $reason = ''): self
    {
        return new self("Failed to write progress to key '$key'" . ($reason ? ": $reason" : ""));
    }

    public static function readFailed(string $key, string $reason = ''): self
    {
        return new self("Failed to read progress from key '$key'" . ($reason ? ": $reason" : ""));
    }

    public static function deleteFailed(string $key, string $reason = ''): self
    {
        return new self("Failed to delete progress key '$key'" . ($reason ? ": $reason" : ""));
    }

    public static function jsonEncodeFailed(string $reason): self
    {
        return new self("Failed to encode progress data: $reason");
    }

    public static function jsonDecodeFailed(string $reason): self
    {
        return new self("Failed to decode progress data: $reason");
    }

    public static function cleanupFailed(string $reason): self
    {
        return new self("Failed to cleanup progress data: $reason");
    }
} 