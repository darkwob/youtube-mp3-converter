<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter\Exceptions;

/**
 * Exception thrown when URL validation fails
 * 
 * @requires PHP >=8.4
 */
class InvalidUrlException extends ConverterException
{
    public static function malformedUrl(string $url): self
    {
        return new self("Malformed URL provided: $url");
    }

    public static function unsupportedPlatform(string $url): self
    {
        return new self("URL from unsupported platform: $url. Only YouTube URLs are supported.");
    }

    public static function emptyUrl(): self
    {
        return new self("URL cannot be empty");
    }

    public static function invalidProtocol(string $url): self
    {
        return new self("Invalid protocol in URL: $url. Only HTTP and HTTPS are supported.");
    }

    public static function invalidYouTubeUrl(string $url): self
    {
        return new self("Invalid YouTube URL format: $url");
    }

    public static function privateVideo(string $url): self
    {
        return new self("Video is private or unavailable: $url");
    }

    public static function ageRestricted(string $url): self
    {
        return new self("Video is age-restricted and cannot be processed: $url");
    }
    
    public static function invalidPlaylistUrl(string $url): self
    {
        return new self("Invalid YouTube playlist URL. URL must contain 'list=' parameter. Got: {$url}");
    }
}