<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter\Util;

use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;

/**
 * Directory management utility for temp and output folder operations
 * 
 * Handles creation, validation, and cleanup of directories with Windows-specific
 * permission handling and path normalization.
 * 
 * @package Darkwob\YoutubeMp3Converter
 * @requires PHP >=8.4
 */
class DirectoryManager
{
    private string $outputPath;
    private string $tempPath;
    private array $createdTempDirectories = [];

    /**
     * Constructor accepting output and temp paths
     * 
     * @param string $outputPath Path where final converted files will be stored
     * @param string $tempPath Path for temporary files during processing
     */
    public function __construct(string $outputPath, string $tempPath)
    {
        $this->outputPath = $this->normalizeWindowsPath($outputPath);
        $this->tempPath = $this->normalizeWindowsPath($tempPath);
    }

    /**
     * Ensure both output and temp directories exist with proper permissions
     * 
     * @throws ConverterException If directories cannot be created or validated
     */
    public function ensureDirectoriesExist(): void
    {
        $this->ensureDirectoryExists($this->outputPath, 'output');
        $this->ensureDirectoryExists($this->tempPath, 'temp');
        
        $this->validateDirectoryPermissions($this->outputPath, 'output');
        $this->validateDirectoryPermissions($this->tempPath, 'temp');
    }

    /**
     * Create a temporary directory with unique prefix
     * 
     * @param string $prefix Prefix for the temporary directory name
     * @return string Full path to the created temporary directory
     * @throws ConverterException If temp directory cannot be created
     */
    public function createTempDirectory(string $prefix = 'ytmp3_'): string
    {
        $this->ensureDirectoryExists($this->tempPath, 'temp');
        
        $uniqueId = uniqid($prefix, true);
        $tempDir = $this->tempPath . DIRECTORY_SEPARATOR . $uniqueId;
        $normalizedTempDir = $this->normalizeWindowsPath($tempDir);
        
        if (!mkdir($normalizedTempDir, 0755, true)) {
            $error = error_get_last();
            throw ConverterException::invalidPath(
                "Failed to create temporary directory: {$normalizedTempDir}. " .
                "Error: " . ($error['message'] ?? 'Unknown error')
            );
        }
        
        // Track created temp directories for cleanup
        $this->createdTempDirectories[] = $normalizedTempDir;
        
        return $normalizedTempDir;
    }

    /**
     * Clean up temporary files and directories
     * 
     * @param string|null $specificPath Optional specific path to clean up
     * @throws ConverterException If cleanup fails
     */
    public function cleanupTempFiles(?string $specificPath = null): void
    {
        if ($specificPath !== null) {
            $this->removeDirectory($specificPath);
            return;
        }
        
        // Clean up all tracked temp directories
        foreach ($this->createdTempDirectories as $tempDir) {
            if (is_dir($tempDir)) {
                $this->removeDirectory($tempDir);
            }
        }
        
        // Clear the tracking array
        $this->createdTempDirectories = [];
    }

    /**
     * Validate directory permissions with Windows-specific checks
     * 
     * @param string $path Directory path to validate
     * @param string $type Type of directory (for error messages)
     * @throws ConverterException If permissions are insufficient
     */
    public function validateDirectoryPermissions(string $path, string $type = 'directory'): void
    {
        if (!is_dir($path)) {
            throw ConverterException::invalidPath("Directory does not exist: {$path}");
        }
        
        // Check if directory is readable
        if (!is_readable($path)) {
            throw ConverterException::invalidPath(
                "Directory is not readable: {$path}. " .
                $this->getPermissionFixInstructions($path, 'read')
            );
        }
        
        // Check if directory is writable
        if (!is_writable($path)) {
            throw ConverterException::invalidPath(
                "Directory is not writable: {$path}. " .
                $this->getPermissionFixInstructions($path, 'write')
            );
        }
        
        // Windows-specific checks
        if (PlatformDetector::isWindows()) {
            $this->validateWindowsPath($path);
        }
    }

    /**
     * Get the output directory path
     */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    /**
     * Get the temp directory path
     */
    public function getTempPath(): string
    {
        return $this->tempPath;
    }

    /**
     * Check if a path is within the managed temp directory
     */
    public function isTempPath(string $path): bool
    {
        $normalizedPath = $this->normalizeWindowsPath($path);
        return str_starts_with($normalizedPath, $this->tempPath);
    }

    /**
     * Ensure a single directory exists
     * 
     * @param string $path Directory path
     * @param string $type Type for error messages
     * @throws ConverterException If directory cannot be created
     */
    private function ensureDirectoryExists(string $path, string $type): void
    {
        if (is_dir($path)) {
            return;
        }
        
        if (!mkdir($path, 0755, true)) {
            $error = error_get_last();
            throw ConverterException::invalidPath(
                "Failed to create {$type} directory: {$path}. " .
                "Error: " . ($error['message'] ?? 'Unknown error')
            );
        }
    }

    /**
     * Recursively remove a directory and its contents
     * 
     * @param string $path Directory path to remove
     * @throws ConverterException If removal fails
     */
    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                if (!rmdir($file->getPathname())) {
                    throw ConverterException::invalidPath(
                        "Failed to remove directory: {$file->getPathname()}"
                    );
                }
            } else {
                if (!unlink($file->getPathname())) {
                    throw ConverterException::invalidPath(
                        "Failed to remove file: {$file->getPathname()}"
                    );
                }
            }
        }
        
        if (!rmdir($path)) {
            throw ConverterException::invalidPath("Failed to remove directory: {$path}");
        }
    }

    /**
     * Normalize path for Windows platform with enhanced handling
     * 
     * @param string $path Path to normalize
     * @return string Normalized path
     */
    public function normalizeWindowsPath(string $path): string
    {
        if (!PlatformDetector::isWindows()) {
            // On non-Windows platforms, use standard normalization
            return PlatformDetector::normalizePath($path);
        }
        
        // Convert forward slashes to backslashes for Windows
        $path = str_replace('/', '\\', $path);
        
        // Remove double backslashes
        $path = preg_replace('#\\\\+#', '\\', $path);
        
        // Handle UNC paths (\\server\share)
        if (str_starts_with($path, '\\\\')) {
            $path = '\\\\' . ltrim($path, '\\');
        }
        
        // Convert relative paths to absolute if needed
        if (!$this->isAbsolutePath($path)) {
            $path = getcwd() . '\\' . ltrim($path, '\\');
        }
        
        // Remove trailing backslash except for root paths
        if (strlen($path) > 3 && str_ends_with($path, '\\')) {
            $path = rtrim($path, '\\');
        }
        
        // Validate the normalized path
        $this->validateWindowsPath($path);
        
        return $path;
    }
    
    /**
     * Check if a path is absolute on Windows
     * 
     * @param string $path Path to check
     * @return bool True if path is absolute
     */
    private function isAbsolutePath(string $path): bool
    {
        if (PlatformDetector::isWindows()) {
            // Windows absolute paths: C:\, \\server\share, or \
            return preg_match('/^([A-Za-z]:[\\\\\\/]|\\\\\\\\|[\\\\\\/])/', $path) === 1;
        }
        
        // Unix-like systems
        return str_starts_with($path, '/');
    }
    
    /**
     * Validate Windows-specific path constraints with enhanced checking
     * 
     * @param string $path Path to validate
     * @throws ConverterException If path is invalid for Windows
     */
    public function validateWindowsPath(string $path): void
    {
        if (!PlatformDetector::isWindows()) {
            return; // Skip validation on non-Windows platforms
        }
        
        // Check path length (Windows has a 260 character limit for full paths)
        if (strlen($path) > 260) {
            throw ConverterException::invalidPath(
                "Path too long for Windows (max 260 characters): {$path}"
            );
        }
        
        // Check for invalid Windows characters
        $invalidChars = ['<', '>', '"', '|', '?', '*'];
        foreach ($invalidChars as $char) {
            if (str_contains($path, $char)) {
                throw ConverterException::invalidPath(
                    "Path contains invalid Windows character '{$char}': {$path}"
                );
            }
        }
        
        // Check for invalid colon usage (only allowed after drive letter)
        if (preg_match('/(?<!^[A-Za-z]):/', $path)) {
            throw ConverterException::invalidPath(
                "Invalid colon usage in Windows path: {$path}"
            );
        }
        
        // Check for reserved Windows names
        $pathParts = explode('\\', $path);
        $reservedNames = [
            'CON', 'PRN', 'AUX', 'NUL',
            'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
            'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'
        ];
        
        foreach ($pathParts as $part) {
            if (empty($part)) {
                continue;
            }
            
            $baseName = strtoupper(pathinfo($part, PATHINFO_FILENAME));
            if (in_array($baseName, $reservedNames, true)) {
                throw ConverterException::invalidPath(
                    "Path contains reserved Windows name '{$part}': {$path}"
                );
            }
            
            // Check for trailing spaces or dots (not allowed in Windows)
            if (rtrim($part, ' .') !== $part) {
                throw ConverterException::invalidPath(
                    "Path component cannot end with space or dot on Windows: '{$part}' in {$path}"
                );
            }
        }
        
        // Check for control characters (0-31)
        if (preg_match('/[\x00-\x1F]/', $path)) {
            throw ConverterException::invalidPath(
                "Path contains control characters: {$path}"
            );
        }
    }

    /**
     * Get platform-specific permission fix instructions
     * 
     * @param string $path Directory path
     * @param string $permission Type of permission (read/write)
     * @return string Instructions for fixing permissions
     */
    private function getPermissionFixInstructions(string $path, string $permission): string
    {
        if (PlatformDetector::isWindows()) {
            return "On Windows, check folder properties and ensure you have {$permission} permissions. " .
                   "You may need to run as administrator or change folder security settings.";
        }
        
        $mode = $permission === 'write' ? '755' : '644';
        return "Fix with: chmod {$mode} {$path}";
    }
}