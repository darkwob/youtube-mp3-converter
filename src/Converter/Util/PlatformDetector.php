<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter\Util;

/**
 * Cross-platform binary management utility
 * 
 * Handles platform-specific binaries that users manually place in their project's bin/ directory.
 * Supports optional custom paths and automatic platform detection.
 * 
 * @package Darkwob\YoutubeMp3Converter
 * @requires PHP >=8.4
 */
class PlatformDetector
{
    public const WINDOWS = 'windows';
    public const LINUX = 'linux';
    public const MACOS = 'macos';
    public const UNKNOWN = 'unknown';

    private static ?string $platform = null;
    private static ?string $projectRootPath = null;

    /**
     * Get executable path for a binary
     * 
     * This is the main helper function that handles the logic:
     * 1. If custom path provided, validate and return it
     * 2. If no path provided, look in bin/ directory for platform-specific binary
     * 
     * @param string $binaryName Name of the binary (e.g., 'yt-dlp', 'ffmpeg')
     * @param string|null $customPath Optional custom path/filename provided by user
     * @return string Full executable path ready for proc_open() or shell_exec()
     * @throws \RuntimeException If binary not found or not executable
     */
    public static function getExecutablePath(string $binaryName, ?string $customPath = null): string
    {
        if ($customPath !== null) {
            return self::validateCustomPath($customPath);
        }
        
        return self::findInBinDirectory($binaryName);
    }

    /**
     * Validate and return custom path provided by user
     */
    private static function validateCustomPath(string $customPath): string
    {
        // If it's just a filename, look in bin directory
        if (basename($customPath) === $customPath) {
            $fullPath = self::getBinPath() . DIRECTORY_SEPARATOR . $customPath;
        } else {
            // It's a full or relative path
            $fullPath = $customPath;
        }
        
        $normalizedPath = self::normalizePath($fullPath);
        
        if (!file_exists($normalizedPath)) {
            throw new \RuntimeException(
                "Custom binary not found: {$normalizedPath}"
            );
        }
        
        if (!is_executable($normalizedPath)) {
            throw new \RuntimeException(
                "Custom binary is not executable: {$normalizedPath}. " .
                "Please check file permissions."
            );
        }
        
        return $normalizedPath;
    }

    /**
     * Find binary in bin/ directory using platform-specific naming
     */
    private static function findInBinDirectory(string $binaryName): string
    {
        $binPath = self::getBinPath();
        $platformSpecificName = self::getBinaryFilename($binaryName);
        $fullPath = $binPath . DIRECTORY_SEPARATOR . $platformSpecificName;
        $normalizedPath = self::normalizePath($fullPath);
        
        if (!file_exists($normalizedPath)) {
            $instructions = self::getInstallationInstructions($binaryName);
            throw new \RuntimeException(
                "Binary '{$binaryName}' not found at: {$normalizedPath}\n\n" .
                "Installation instructions:\n{$instructions}"
            );
        }
        
        if (!is_executable($normalizedPath)) {
            throw new \RuntimeException(
                "Binary '{$binaryName}' exists but is not executable: {$normalizedPath}\n" .
                "Fix with: chmod +x {$normalizedPath}"
            );
        }
        
        return $normalizedPath;
    }

    /**
     * Execute a binary with arguments (convenience method)
     * 
     * @param string $binaryName Name of the binary
     * @param array $arguments Command arguments
     * @param string|null $customPath Optional custom binary path
     * @return array Execution result with output, return code, etc.
     */
    public static function executeBinary(string $binaryName, array $arguments = [], ?string $customPath = null): array
    {
        $binaryPath = self::getExecutablePath($binaryName, $customPath);
        
        // Escape the binary path and arguments for shell execution
        $escapedBinaryPath = escapeshellarg($binaryPath);
        $escapedArgs = array_map('escapeshellarg', $arguments);
        
        // Build the command
        $command = $escapedBinaryPath . ' ' . implode(' ', $escapedArgs);
        
        // Execute the command
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        return [
            'command' => $command,
            'output' => $output,
            'return_code' => $returnCode,
            'success' => $returnCode === 0,
            'binary_path' => $binaryPath
        ];
    }

    /**
     * Create command array for proc_open() or Process classes
     * 
     * @param string $binaryName Name of the binary
     * @param array $arguments Command arguments
     * @param string|null $customPath Optional custom binary path
     * @return array Command array ready for proc_open()
     */
    public static function createCommand(string $binaryName, array $arguments = [], ?string $customPath = null): array
    {
        $binaryPath = self::getExecutablePath($binaryName, $customPath);
        return array_merge([$binaryPath], $arguments);
    }

    /**
     * Check if a binary exists (convenience method)
     * 
     * @param string $binaryName Name of the binary
     * @param string|null $customPath Optional custom binary path
     * @return bool True if binary exists and is executable
     */
    public static function binaryExists(string $binaryName, ?string $customPath = null): bool
    {
        try {
            self::getExecutablePath($binaryName, $customPath);
            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }

    /**
     * Detect current platform
     */
    public static function detect(): string
    {
        if (self::$platform !== null) {
            return self::$platform;
        }

        $os = strtolower(PHP_OS_FAMILY);
        
        self::$platform = match ($os) {
            'windows' => self::WINDOWS,
            'darwin' => self::MACOS,
            'linux' => self::LINUX,
            default => self::UNKNOWN
        };

        return self::$platform;
    }

    /**
     * Check if running on Windows
     */
    public static function isWindows(): bool
    {
        return self::detect() === self::WINDOWS;
    }

    /**
     * Check if running on macOS
     */
    public static function isMacOS(): bool
    {
        return self::detect() === self::MACOS;
    }

    /**
     * Check if running on Linux
     */
    public static function isLinux(): bool
    {
        return self::detect() === self::LINUX;
    }

    /**
     * Get the project root directory (where composer.json is located)
     */
    public static function getProjectRoot(): string
    {
        if (self::$projectRootPath !== null) {
            return self::$projectRootPath;
        }

        // Start from current working directory
        $currentDir = getcwd();
        
        // Look for composer.json to identify project root
        $dir = $currentDir;
        while ($dir !== dirname($dir)) { // Continue until we reach filesystem root
            if (file_exists($dir . DIRECTORY_SEPARATOR . 'composer.json')) {
                self::$projectRootPath = $dir;
                return self::$projectRootPath;
            }
            $dir = dirname($dir);
        }
        
        // Fallback to current working directory
        self::$projectRootPath = $currentDir;
        return self::$projectRootPath;
    }

    /**
     * Get the bin directory path in the user's project root
     */
    public static function getBinPath(): string
    {
        $projectRoot = self::getProjectRoot();
        return self::normalizePath($projectRoot . DIRECTORY_SEPARATOR . 'bin');
    }

    /**
     * Get platform-specific binary filename
     */
    public static function getBinaryFilename(string $binaryName): string
    {
        $platform = self::detect();
        
        return match ($platform) {
            self::WINDOWS => $binaryName . '.exe',
            self::LINUX, self::MACOS => $binaryName,
            default => $binaryName
        };
    }

    /**
     * Get full path to a platform-specific binary in the project's bin directory
     */
    public static function getBinaryPath(string $binaryName): string
    {
        $binPath = self::getBinPath();
        $filename = self::getBinaryFilename($binaryName);
        
        return self::normalizePath($binPath . DIRECTORY_SEPARATOR . $filename);
    }

    /**
     * Normalize file path for current platform
     */
    public static function normalizePath(string $path): string
    {
        // Convert all separators to platform-specific ones
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        
        // Remove double separators
        $path = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR) . '+#', DIRECTORY_SEPARATOR, $path);
        
        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Get platform-specific executable extension
     */
    public static function getExecutableExtension(): string
    {
        return self::isWindows() ? '.exe' : '';
    }

    /**
     * Create bin directory if it doesn't exist
     */
    public static function createBinDirectory(): bool
    {
        $binPath = self::getBinPath();
        
        if (!is_dir($binPath)) {
            return mkdir($binPath, 0755, true);
        }
        
        return true;
    }

    /**
     * Get suggested binary download URLs for each platform
     */
    public static function getBinaryDownloadInfo(string $binaryName): array
    {
        return match ($binaryName) {
            'ffmpeg' => [
                self::WINDOWS => 'https://www.gyan.dev/ffmpeg/builds/ffmpeg-release-essentials.zip',
                self::LINUX => 'https://johnvansickle.com/ffmpeg/releases/ffmpeg-release-amd64-static.tar.xz',
                self::MACOS => 'https://evermeet.cx/ffmpeg/ffmpeg-latest.zip'
            ],
            'yt-dlp' => [
                self::WINDOWS => 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp.exe',
                self::LINUX => 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp',
                self::MACOS => 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp_macos'
            ],
            default => []
        };
    }

    /**
     * Get installation instructions for a binary
     */
    public static function getInstallationInstructions(string $binaryName): string
    {
        $binPath = self::getBinPath();
        $filename = self::getBinaryFilename($binaryName);
        $downloadInfo = self::getBinaryDownloadInfo($binaryName);
        $currentPlatform = self::detect();
        
        if (!isset($downloadInfo[$currentPlatform])) {
            return "No download information available for {$binaryName} on {$currentPlatform}";
        }
        
        $downloadUrl = $downloadInfo[$currentPlatform];
        
        return "To install {$binaryName}:\n" .
               "1. Create bin directory: {$binPath}\n" .
               "2. Download from: {$downloadUrl}\n" .
               "3. Extract and place the executable as: {$binPath}" . DIRECTORY_SEPARATOR . "{$filename}\n" .
               "4. Make sure the file is executable (chmod +x on Unix systems)";
    }

    /**
     * Get platform information as array
     */
    public static function getPlatformInfo(): array
    {
        return [
            'platform' => self::detect(),
            'is_windows' => self::isWindows(),
            'is_linux' => self::isLinux(),
            'is_macos' => self::isMacOS(),
            'directory_separator' => DIRECTORY_SEPARATOR,
            'executable_extension' => self::getExecutableExtension(),
            'project_root' => self::getProjectRoot(),
            'bin_path' => self::getBinPath()
        ];
    }

    /**
     * Check system requirements and provide setup instructions
     */
    public static function checkRequirements(array $requiredBinaries = ['ffmpeg', 'yt-dlp']): array
    {
        $results = [];
        
        foreach ($requiredBinaries as $binary) {
            $exists = self::binaryExists($binary);
            $path = $exists ? self::getBinaryPath($binary) : null;
            
            $results[$binary] = [
                'exists' => $exists,
                'path' => $path,
                'instructions' => $exists ? null : self::getInstallationInstructions($binary)
            ];
        }
        
        return $results;
    }
} 