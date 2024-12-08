<?php

namespace Darkwob\YoutubeMp3Converter\Converter\Exceptions;

class ConverterException extends \Exception
{
    public static function videoInfoFailed(string $message = ''): self
    {
        return new self("Failed to get video information: {$message}");
    }

    public static function downloadFailed(string $message = ''): self
    {
        return new self("Failed to download video: {$message}");
    }

    public static function conversionFailed(string $message = ''): self
    {
        return new self("Failed to convert video: {$message}");
    }

    public static function invalidUrl(string $url): self
    {
        return new self("Invalid YouTube URL: {$url}");
    }

    public static function missingDependency(string $dependency): self
    {
        return new self("Missing dependency: {$dependency}");
    }

    public static function invalidOutputDirectory(string $dir): self
    {
        return new self("Invalid output directory: {$dir}");
    }
} 