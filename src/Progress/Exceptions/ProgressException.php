<?php

namespace Darkwob\YoutubeMp3Converter\Progress\Exceptions;

class ProgressException extends \Exception
{
    public static function unableToWrite(string $path): self
    {
        return new self("Unable to write progress file: {$path}");
    }

    public static function unableToRead(string $path): self
    {
        return new self("Unable to read progress file: {$path}");
    }

    public static function invalidJson(string $path): self
    {
        return new self("Invalid JSON in progress file: {$path}");
    }

    public static function invalidProgress(string $message): self
    {
        return new self("Invalid progress data: {$message}");
    }
} 