<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter\Exceptions;

/**
 * Exception thrown for network connection and timeout issues
 * 
 * @requires PHP >=8.4
 */
class NetworkException extends ConverterException
{
    public static function connectionFailed(string $url, string $reason = ''): self
    {
        $message = "Failed to connect to: $url";
        if (!empty($reason)) {
            $message .= "\nReason: $reason";
        }
        
        $message .= "\n\nTroubleshooting:\n";
        $message .= "1. Check internet connection\n";
        $message .= "2. Verify URL is accessible\n";
        $message .= "3. Check firewall settings\n";
        $message .= "4. Try again later if service is temporarily down";
        
        return new self($message);
    }

    public static function timeout(string $url, int $timeoutSeconds): self
    {
        $message = "Connection timed out after $timeoutSeconds seconds: $url\n\n";
        $message .= "Possible causes:\n";
        $message .= "1. Slow internet connection\n";
        $message .= "2. Server is overloaded\n";
        $message .= "3. Large file download taking too long\n";
        $message .= "4. Network congestion\n\n";
        $message .= "Solutions:\n";
        $message .= "1. Increase timeout value\n";
        $message .= "2. Check network speed\n";
        $message .= "3. Try during off-peak hours\n";
        $message .= "4. Use a different network connection";
        
        return new self($message);
    }

    public static function dnsResolutionFailed(string $hostname): self
    {
        $message = "DNS resolution failed for: $hostname\n\n";
        $message .= "Solutions:\n";
        $message .= "1. Check DNS server settings\n";
        $message .= "2. Try using public DNS (8.8.8.8, 1.1.1.1)\n";
        $message .= "3. Flush DNS cache\n";
        $message .= "4. Check if hostname is correct";
        
        return new self($message);
    }

    public static function sslError(string $url, string $sslError): self
    {
        $message = "SSL/TLS error for: $url\n";
        $message .= "SSL Error: $sslError\n\n";
        $message .= "Common SSL issues:\n";
        $message .= "1. Certificate expired or invalid\n";
        $message .= "2. Hostname mismatch\n";
        $message .= "3. Outdated SSL/TLS version\n";
        $message .= "4. Self-signed certificate\n\n";
        $message .= "Note: For security reasons, SSL verification should not be disabled.";
        
        return new self($message);
    }

    public static function httpError(string $url, int $statusCode, string $statusText = ''): self
    {
        $message = "HTTP error $statusCode for: $url";
        if (!empty($statusText)) {
            $message .= " ($statusText)";
        }
        
        $message .= "\n\nHTTP status meanings:\n";
        $message .= match (true) {
            $statusCode >= 400 && $statusCode < 500 => "4xx: Client error - check URL and parameters",
            $statusCode >= 500 && $statusCode < 600 => "5xx: Server error - try again later",
            $statusCode >= 300 && $statusCode < 400 => "3xx: Redirection - URL may have moved",
            default => "Unexpected status code"
        };
        
        return new self($message);
    }

    public static function rateLimited(string $service, int $retryAfterSeconds = 0): self
    {
        $message = "Rate limited by service: $service";
        
        if ($retryAfterSeconds > 0) {
            $message .= "\nRetry after: $retryAfterSeconds seconds";
        }
        
        $message .= "\n\nRate limiting info:\n";
        $message .= "1. Too many requests in short time period\n";
        $message .= "2. Service has imposed temporary restrictions\n";
        $message .= "3. Wait before retrying\n";
        $message .= "4. Consider implementing request throttling";
        
        return new self($message);
    }

    public static function proxyError(string $proxyUrl, string $reason = ''): self
    {
        $message = "Proxy connection failed: $proxyUrl";
        if (!empty($reason)) {
            $message .= "\nReason: $reason";
        }
        
        $message .= "\n\nProxy troubleshooting:\n";
        $message .= "1. Verify proxy server is running\n";
        $message .= "2. Check proxy credentials\n";
        $message .= "3. Ensure proxy allows HTTPS connections\n";
        $message .= "4. Test proxy with other applications";
        
        return new self($message);
    }

    public static function downloadInterrupted(string $url, int $bytesReceived, int $expectedBytes = 0): self
    {
        $message = "Download interrupted for: $url\n";
        $message .= "Bytes received: $bytesReceived";
        
        if ($expectedBytes > 0) {
            $percentage = round(($bytesReceived / $expectedBytes) * 100, 2);
            $message .= " / $expectedBytes ($percentage%)";
        }
        
        $message .= "\n\nPossible causes:\n";
        $message .= "1. Network connection lost\n";
        $message .= "2. Server closed connection\n";
        $message .= "3. Insufficient disk space\n";
        $message .= "4. Process was interrupted";
        
        return new self($message);
    }

    public static function geoBlocked(string $url, string $country = ''): self
    {
        $message = "Content is geo-blocked: $url";
        if (!empty($country)) {
            $message .= "\nBlocked in: $country";
        }
        
        $message .= "\n\nGeo-blocking info:\n";
        $message .= "1. Content not available in your region\n";
        $message .= "2. Copyright restrictions apply\n";
        $message .= "3. Service provider limitations\n";
        $message .= "4. Try accessing from a different location";
        
        return new self($message);
    }

    public static function serviceUnavailable(string $service, string $reason = ''): self
    {
        $message = "Service unavailable: $service";
        if (!empty($reason)) {
            $message .= "\nReason: $reason";
        }
        
        $message .= "\n\nService may be:\n";
        $message .= "1. Temporarily down for maintenance\n";
        $message .= "2. Experiencing high load\n";
        $message .= "3. Having technical difficulties\n";
        $message .= "4. Check service status page for updates";
        
        return new self($message);
    }
}