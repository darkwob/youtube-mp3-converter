<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter\Exceptions;

/**
 * @requires PHP >=8.4
 */
class ConverterException extends \Exception
{
    public static function invalidUrl(string $url): self
    {
        return new self("Invalid URL: $url");
    }

    public static function downloadFailed(string $url, string $reason): self
    {
        return new self("Download failed for URL: $url. Reason: $reason");
    }

    public static function conversionFailed(string $file, string $reason): self
    {
        return new self("Conversion failed for file: $file. Reason: $reason");
    }

    public static function processingFailed(string $reason): self
    {
        return new self("Processing failed: $reason");
    }

    public static function fileNotFound(string $path): self
    {
        return new self("File not found: $path");
    }

    public static function invalidFormat(string $format): self
    {
        return new self("Invalid format: $format");
    }

    public static function invalidQuality(int $quality): self
    {
        return new self("Invalid quality value: $quality. Must be between 0 and 9");
    }

    public static function invalidPath(string $message): self
    {
        return new self("Invalid path: $message");
    }

    public static function missingDependency(string $dependency): self
    {
        return new self("Missing dependency: $dependency");
    }

    public static function invalidConfiguration(string $reason): self
    {
        return new self("Invalid configuration: $reason");
    }

    public static function remoteServerError(string $url, string $reason): self
    {
        return new self("Remote server error at $url: $reason");
    }

    public static function invalidOutputDirectory(string $path): self
    {
        return new self("Invalid output directory: $path");
    }

    public static function videoInfoFailed(string $reason): self
    {
        return new self("Failed to get video info: $reason");
    }

    public static function networkError(string $reason): self
    {
        return new self("Network error: $reason");
    }

    public static function authenticationFailed(string $reason): self
    {
        return new self("Authentication failed: $reason");
    }

    public static function quotaExceeded(string $service): self
    {
        return new self("Quota exceeded for service: $service");
    }

    public static function unsupportedUrl(string $url): self
    {
        return new self("Unsupported URL: $url");
    }

    public static function timeoutError(string $operation): self
    {
        return new self("Timeout during operation: $operation");
    }
} 