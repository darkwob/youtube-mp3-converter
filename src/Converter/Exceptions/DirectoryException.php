<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter\Exceptions;

/**
 * Exception thrown for directory creation and permission issues
 * 
 * @requires PHP >=8.4
 */
class DirectoryException extends ConverterException
{
    public static function creationFailed(string $path, string $reason = ''): self
    {
        $message = "Failed to create directory: $path";
        if (!empty($reason)) {
            $message .= ". Reason: $reason";
        }
        
        $message .= "\n\nTroubleshooting:\n";
        $message .= "1. Check parent directory permissions\n";
        $message .= "2. Ensure sufficient disk space\n";
        $message .= "3. Verify path is valid for your operating system\n";
        $message .= "4. On Windows: Check for invalid characters in path";
        
        return new self($message);
    }

    public static function permissionDenied(string $path): self
    {
        $message = "Permission denied for directory: $path\n\n";
        $message .= "Fix instructions:\n";
        $message .= "1. On Linux/Mac: chmod 755 $path or sudo chown \$USER $path\n";
        $message .= "2. On Windows: Right-click → Properties → Security → Edit permissions\n";
        $message .= "3. Ensure the web server user has write access\n";
        $message .= "4. Check if directory is read-only";
        
        return new self($message);
    }

    public static function notWritable(string $path): self
    {
        $message = "Directory is not writable: $path\n\n";
        $message .= "Solutions:\n";
        $message .= "1. Change directory permissions to allow writing\n";
        $message .= "2. Check if disk is full\n";
        $message .= "3. Verify directory is not mounted as read-only\n";
        $message .= "4. On Windows: Disable read-only attribute";
        
        return new self($message);
    }

    public static function doesNotExist(string $path): self
    {
        return new self("Directory does not exist: $path");
    }

    public static function cleanupFailed(string $path, string $reason = ''): self
    {
        $message = "Failed to cleanup directory: $path";
        if (!empty($reason)) {
            $message .= ". Reason: $reason";
        }
        
        return new self($message);
    }

    public static function invalidWindowsPath(string $path): self
    {
        $message = "Invalid Windows path: $path\n\n";
        $message .= "Windows path restrictions:\n";
        $message .= "1. Cannot contain: < > : \" | ? * \n";
        $message .= "2. Cannot end with space or period\n";
        $message .= "3. Maximum path length: 260 characters (unless long path support enabled)\n";
        $message .= "4. Reserved names: CON, PRN, AUX, NUL, COM1-9, LPT1-9\n";
        $message .= "5. Use backslashes (\\) as path separators";
        
        return new self($message);
    }

    public static function pathTooLong(string $path, int $maxLength = 260): self
    {
        $currentLength = strlen($path);
        $message = "Path too long: $path\n";
        $message .= "Current length: $currentLength characters, Maximum: $maxLength\n\n";
        $message .= "Solutions:\n";
        $message .= "1. Use shorter directory names\n";
        $message .= "2. Move to a location closer to root\n";
        $message .= "3. On Windows 10+: Enable long path support in Group Policy";
        
        return new self($message);
    }

    public static function containsInvalidCharacters(string $path, array $invalidChars): self
    {
        $message = "Path contains invalid characters: $path\n";
        $message .= "Invalid characters found: " . implode(', ', $invalidChars) . "\n\n";
        $message .= "Please remove or replace these characters with valid alternatives.";
        
        return new self($message);
    }

    public static function reservedName(string $path, string $reservedName): self
    {
        $message = "Path contains reserved name '$reservedName': $path\n\n";
        $message .= "Reserved names on Windows: CON, PRN, AUX, NUL, COM1-9, LPT1-9\n";
        $message .= "Please use a different name.";
        
        return new self($message);
    }

    public static function tempDirectoryFailed(string $reason = ''): self
    {
        $message = "Failed to create temporary directory";
        if (!empty($reason)) {
            $message .= ": $reason";
        }
        
        $message .= "\n\nCheck:\n";
        $message .= "1. System temp directory permissions\n";
        $message .= "2. Available disk space\n";
        $message .= "3. TEMP/TMP environment variables";
        
        return new self($message);
    }
}