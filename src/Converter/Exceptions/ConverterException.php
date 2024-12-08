<?php

namespace Darkwob\YoutubeMp3Converter\Converter\Exceptions;

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

    public static function playlistError(string $url, string $reason): self
    {
        return new self("Playlist error for URL $url: $reason");
    }

    public static function networkError(string $url, string $reason): self
    {
        return new self("Network error while accessing $url: $reason");
    }

    public static function rateLimitExceeded(string $url): self
    {
        return new self("Rate limit exceeded for URL: $url");
    }

    public static function geoRestricted(string $url): self
    {
        return new self("Content is geo-restricted: $url");
    }

    public static function unsupportedPlatform(string $url): self
    {
        return new self("Unsupported platform or URL: $url");
    }

    public static function invalidResponse(string $url, string $reason): self
    {
        return new self("Invalid response from $url: $reason");
    }

    public static function processingTimeout(string $url): self
    {
        return new self("Processing timeout for URL: $url");
    }

    public static function diskSpaceError(string $path): self
    {
        return new self("Insufficient disk space at: $path");
    }

    public static function permissionError(string $path): self
    {
        return new self("Permission denied: $path");
    }

    public static function metadataError(string $file, string $reason): self
    {
        return new self("Metadata error for file $file: $reason");
    }

    public static function thumbnailError(string $url, string $reason): self
    {
        return new self("Thumbnail error for URL $url: $reason");
    }

    public static function videoInfoFailed(string $reason = ''): self
    {
        return new self("Failed to get video info" . ($reason ? ": $reason" : ""));
    }

    public static function invalidOutputDirectory(string $directory): self
    {
        return new self("Cannot create output directory: $directory");
    }
} 