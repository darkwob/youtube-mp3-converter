<?php

declare(strict_types=1);

namespace Darkwob\YoutubeMp3Converter\Converter\Util;

use Darkwob\YoutubeMp3Converter\Converter\ProcessResult;
use Darkwob\YoutubeMp3Converter\Converter\Exceptions\ConverterException;
use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

/**
 * Process manager for executing yt-dlp and ffmpeg binaries
 * 
 * Handles binary execution with Windows environment setup, timeout handling,
 * and error output parsing using Symfony Process component.
 * 
 * @package Darkwob\YoutubeMp3Converter
 * @requires PHP >=8.4
 */
class ProcessManager
{
    private const DEFAULT_TIMEOUT = 300; // 5 minutes
    private const VIDEO_INFO_TIMEOUT = 30; // 30 seconds for info extraction
    
    private string $workingDirectory;
    private int $defaultTimeout;
    private ?string $ytDlpPath;
    private ?string $ffmpegPath;
    
    public function __construct(
        string $workingDirectory = '',
        int $defaultTimeout = self::DEFAULT_TIMEOUT,
        ?string $ytDlpPath = null,
        ?string $ffmpegPath = null
    ) {
        $this->workingDirectory = $workingDirectory ?: getcwd();
        $this->defaultTimeout = $defaultTimeout;
        $this->ytDlpPath = $ytDlpPath;
        $this->ffmpegPath = $ffmpegPath;
    }
    
    /**
     * Execute yt-dlp with arguments and working directory support
     */
    public function executeYtDlp(array $arguments, ?string $workingDir = null, ?int $timeout = null): ProcessResult
    {
        $command = PlatformDetector::createCommand('yt-dlp', $arguments, $this->ytDlpPath);
        $process = $this->createProcess($command, $workingDir, $timeout);
        
        return $this->handleProcessResult($process);
    }

    /**
     * Execute yt-dlp with real-time progress callbacks
     */
    public function executeYtDlpWithProgress(array $arguments, ?string $workingDir = null, ?int $timeout = null, ?callable $progressCallback = null): ProcessResult
    {
        $command = PlatformDetector::createCommand('yt-dlp', $arguments, $this->ytDlpPath);
        $process = $this->createProcess($command, $workingDir, $timeout);
        
        return $this->handleProcessResultWithProgress($process, $progressCallback);
    }
    
    /**
     * Execute ffmpeg with process timeout and error handling
     */
    public function executeFfmpeg(array $arguments, ?string $workingDir = null, ?int $timeout = null): ProcessResult
    {
        $command = PlatformDetector::createCommand('ffmpeg', $arguments, $this->ffmpegPath);
        $process = $this->createProcess($command, $workingDir, $timeout);
        
        return $this->handleProcessResult($process);
    }

    /**
     * Execute ffmpeg with real-time progress callbacks
     */
    public function executeFfmpegWithProgress(array $arguments, ?string $workingDir = null, ?int $timeout = null, ?callable $progressCallback = null): ProcessResult
    {
        $command = PlatformDetector::createCommand('ffmpeg', $arguments, $this->ffmpegPath);
        $process = $this->createProcess($command, $workingDir, $timeout);
        
        return $this->handleProcessResultWithProgress($process, $progressCallback);
    }
    
    /**
     * Get video information using yt-dlp info extraction
     */
    public function getVideoInfo(string $url): array
    {
        $arguments = [
            '--dump-json',
            '--no-download',
            '--no-warnings',
            $url
        ];
        
        $result = $this->executeYtDlp($arguments, null, self::VIDEO_INFO_TIMEOUT);
        
        if (!$result->isSuccessful()) {
            throw ConverterException::videoInfoFailed(
                "Failed to extract video info: " . $result->getErrorOutput()
            );
        }
        
        $output = trim($result->getOutput());
        if (empty($output)) {
            throw ConverterException::videoInfoFailed("Empty response from yt-dlp");
        }
        
        $videoInfo = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ConverterException::videoInfoFailed(
                "Invalid JSON response: " . json_last_error_msg()
            );
        }
        
        return $videoInfo;
    }
    
    /**
     * Create Symfony Process with Windows environment setup
     */
    private function createProcess(array $command, ?string $workingDir = null, ?int $timeout = null): Process
    {
        $process = new Process($command);
        
        // Set working directory
        $workingDirectory = $workingDir ?? $this->workingDirectory;
        if (!empty($workingDirectory)) {
            $process->setWorkingDirectory($workingDirectory);
        }
        
        // Set timeout
        $process->setTimeout($timeout ?? $this->defaultTimeout);
        
        // Setup Windows-specific environment
        $this->setupWindowsEnvironment($process);
        
        return $process;
    }
    
    /**
     * Setup Windows-specific environment variables and settings with enhanced handling
     */
    public function setupWindowsEnvironment(Process $process): void
    {
        if (!PlatformDetector::isWindows()) {
            return; // Skip Windows-specific setup on other platforms
        }
        
        $env = $_ENV;
        
        // Enhanced PATH handling for Windows
        $this->setupWindowsPath($env);
        
        // Enhanced TEMP directory handling
        $this->setupWindowsTempDirectory($env);
        
        // Set console and encoding settings
        $this->setupWindowsConsoleSettings($env);
        
        // Set process-specific Windows settings
        $this->setupWindowsProcessSettings($env);
        
        $process->setEnv($env);
    }
    
    /**
     * Setup Windows PATH environment variable with comprehensive binary locations
     * 
     * @param array &$env Environment variables array
     */
    private function setupWindowsPath(array &$env): void
    {
        $currentPath = $env['PATH'] ?? $env['Path'] ?? '';
        
        // Common binary locations on Windows
        $pathDirs = [
            PlatformDetector::getBinPath(),
            // FFmpeg common locations
            'C:\\ffmpeg\\bin',
            'C:\\Program Files\\ffmpeg\\bin',
            'C:\\Program Files (x86)\\ffmpeg\\bin',
            // yt-dlp common locations
            'C:\\yt-dlp',
            'C:\\Program Files\\yt-dlp',
            'C:\\Program Files (x86)\\yt-dlp',
            // Chocolatey locations
            'C:\\ProgramData\\chocolatey\\bin',
            // Scoop locations
            $this->getUserScoopPath(),
            // User local bin
            $this->getUserLocalBinPath(),
            // System32 (already in PATH usually, but ensure it's there)
            'C:\\Windows\\System32'
        ];
        
        // Filter existing directories and normalize paths
        $validPaths = [];
        foreach ($pathDirs as $dir) {
            if (!empty($dir) && is_dir($dir)) {
                $validPaths[] = $this->normalizeWindowsPath($dir);
            }
        }
        
        // Remove duplicates and add to PATH
        $validPaths = array_unique($validPaths);
        
        if (!empty($validPaths)) {
            $newPathEntries = implode(';', $validPaths);
            $env['PATH'] = $newPathEntries . ';' . $currentPath;
        }
        
        // Ensure PATH doesn't have duplicate entries
        $pathEntries = explode(';', $env['PATH']);
        $pathEntries = array_unique(array_filter($pathEntries));
        $env['PATH'] = implode(';', $pathEntries);
    }
    
    /**
     * Setup Windows TEMP directory with proper fallbacks
     * 
     * @param array &$env Environment variables array
     */
    private function setupWindowsTempDirectory(array &$env): void
    {
        // Check existing TEMP/TMP variables
        $tempDir = $env['TEMP'] ?? $env['TMP'] ?? null;
        
        if (empty($tempDir) || !is_dir($tempDir) || !is_writable($tempDir)) {
            // Try common Windows temp locations
            $tempCandidates = [
                sys_get_temp_dir(),
                'C:\\Windows\\Temp',
                'C:\\Temp',
                $this->getUserTempPath()
            ];
            
            foreach ($tempCandidates as $candidate) {
                if (!empty($candidate) && is_dir($candidate) && is_writable($candidate)) {
                    $tempDir = $this->normalizeWindowsPath($candidate);
                    break;
                }
            }
            
            // Create user temp directory if none found
            if (empty($tempDir)) {
                $userTemp = $this->getUserTempPath();
                if (!is_dir($userTemp)) {
                    mkdir($userTemp, 0755, true);
                }
                $tempDir = $this->normalizeWindowsPath($userTemp);
            }
        } else {
            $tempDir = $this->normalizeWindowsPath($tempDir);
        }
        
        $env['TEMP'] = $tempDir;
        $env['TMP'] = $tempDir;
    }
    
    /**
     * Setup Windows console and encoding settings
     * 
     * @param array &$env Environment variables array
     */
    private function setupWindowsConsoleSettings(array &$env): void
    {
        // Set console output encoding to UTF-8
        $env['PYTHONIOENCODING'] = 'utf-8';
        $env['PYTHONUTF8'] = '1';
        
        // Set console code page to UTF-8 if not set
        if (!isset($env['CHCP'])) {
            $env['CHCP'] = '65001';
        }
        
        // Disable Python buffering for real-time output
        $env['PYTHONUNBUFFERED'] = '1';
        
        // Set locale for consistent behavior
        if (!isset($env['LC_ALL'])) {
            $env['LC_ALL'] = 'C.UTF-8';
        }
    }
    
    /**
     * Setup Windows process-specific settings
     * 
     * @param array &$env Environment variables array
     */
    private function setupWindowsProcessSettings(array &$env): void
    {
        // Set process priority class (normal)
        $env['PROCESS_PRIORITY_CLASS'] = 'NORMAL_PRIORITY_CLASS';
        
        // Disable Windows Error Reporting for child processes
        $env['WER_DISABLE_ARCHIVE'] = '1';
        
        // Set working directory in environment
        if (!empty($this->workingDirectory)) {
            $env['PWD'] = $this->normalizeWindowsPath($this->workingDirectory);
        }
    }
    
    /**
     * Get user's Scoop installation path
     * 
     * @return string|null Scoop path or null if not found
     */
    private function getUserScoopPath(): ?string
    {
        $userProfile = $_ENV['USERPROFILE'] ?? null;
        if ($userProfile) {
            $scoopPath = $userProfile . '\\scoop\\shims';
            return is_dir($scoopPath) ? $scoopPath : null;
        }
        return null;
    }
    
    /**
     * Get user's local bin path
     * 
     * @return string|null Local bin path or null if not found
     */
    private function getUserLocalBinPath(): ?string
    {
        $userProfile = $_ENV['USERPROFILE'] ?? null;
        if ($userProfile) {
            $localBinPath = $userProfile . '\\AppData\\Local\\bin';
            return is_dir($localBinPath) ? $localBinPath : null;
        }
        return null;
    }
    
    /**
     * Get user's temp directory path
     * 
     * @return string User temp path
     */
    private function getUserTempPath(): string
    {
        $userProfile = $_ENV['USERPROFILE'] ?? 'C:\\Users\\Default';
        return $userProfile . '\\AppData\\Local\\Temp';
    }
    
    /**
     * Normalize Windows path (wrapper for DirectoryManager method)
     * 
     * @param string $path Path to normalize
     * @return string Normalized path
     */
    private function normalizeWindowsPath(string $path): string
    {
        if (!PlatformDetector::isWindows()) {
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
        
        // Remove trailing backslash except for root paths
        if (strlen($path) > 3 && str_ends_with($path, '\\')) {
            $path = rtrim($path, '\\');
        }
        
        return $path;
    }
    
    /**
     * Handle process result with error output parsing
     */
    private function handleProcessResult(Process $process): ProcessResult
    {
        $startTime = microtime(true);
        
        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            $executionTime = microtime(true) - $startTime;
            
            return new ProcessResult(
                success: false,
                exitCode: -1,
                output: $process->getOutput(),
                errorOutput: "Process timed out after {$process->getTimeout()} seconds",
                executionTime: $executionTime,
                command: $process->getCommandLine(),
                workingDirectory: $process->getWorkingDirectory()
            );
        }
        
        $executionTime = microtime(true) - $startTime;
        $isSuccessful = $process->isSuccessful();
        
        // Parse error output for common issues
        $errorOutput = $process->getErrorOutput();
        if (!$isSuccessful && !empty($errorOutput)) {
            $errorOutput = $this->parseErrorOutput($errorOutput);
        }
        
        return new ProcessResult(
            success: $isSuccessful,
            exitCode: $process->getExitCode(),
            output: $process->getOutput(),
            errorOutput: $errorOutput,
            executionTime: $executionTime,
            command: $process->getCommandLine(),
            workingDirectory: $process->getWorkingDirectory()
        );
    }

    /**
     * Handle process result with real-time progress callbacks
     */
    private function handleProcessResultWithProgress(Process $process, ?callable $progressCallback = null): ProcessResult
    {
        $startTime = microtime(true);
        $output = '';
        $errorOutput = '';
        
        try {
            $process->run(function ($type, $buffer) use (&$output, &$errorOutput, $progressCallback) {
                if ($type === Process::OUT) {
                    $output .= $buffer;
                    
                    // Call progress callback with output buffer
                    if ($progressCallback !== null) {
                        try {
                            $progressCallback($buffer);
                        } catch (\Exception $e) {
                            // Log callback error but don't stop the process
                            error_log("Progress callback error: " . $e->getMessage());
                        }
                    }
                } else {
                    $errorOutput .= $buffer;
                }
            });
        } catch (ProcessTimedOutException $e) {
            $executionTime = microtime(true) - $startTime;
            
            return new ProcessResult(
                success: false,
                exitCode: -1,
                output: $output,
                errorOutput: "Process timed out after {$process->getTimeout()} seconds",
                executionTime: $executionTime,
                command: $process->getCommandLine(),
                workingDirectory: $process->getWorkingDirectory()
            );
        }
        
        $executionTime = microtime(true) - $startTime;
        $isSuccessful = $process->isSuccessful();
        
        // Parse error output for common issues
        if (!$isSuccessful && !empty($errorOutput)) {
            $errorOutput = $this->parseErrorOutput($errorOutput);
        }
        
        return new ProcessResult(
            success: $isSuccessful,
            exitCode: $process->getExitCode(),
            output: $output,
            errorOutput: $errorOutput,
            executionTime: $executionTime,
            command: $process->getCommandLine(),
            workingDirectory: $process->getWorkingDirectory()
        );
    }
    
    /**
     * Parse and enhance error output with helpful information
     */
    private function parseErrorOutput(string $errorOutput): string
    {
        $lines = explode("\n", $errorOutput);
        $enhancedLines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // Add helpful context for common errors
            if (str_contains($line, 'command not found') || str_contains($line, 'is not recognized')) {
                $enhancedLines[] = $line;
                $enhancedLines[] = "Hint: Make sure the binary is installed and available in PATH or bin/ directory";
            } elseif (str_contains($line, 'Permission denied') || str_contains($line, 'Access is denied')) {
                $enhancedLines[] = $line;
                if (PlatformDetector::isWindows()) {
                    $enhancedLines[] = "Hint: Check file permissions and run as administrator if needed";
                } else {
                    $enhancedLines[] = "Hint: Try running 'chmod +x' on the binary file";
                }
            } elseif (str_contains($line, 'No such file or directory')) {
                $enhancedLines[] = $line;
                $enhancedLines[] = "Hint: Check if the file path exists and is accessible";
            } elseif (str_contains($line, 'network') || str_contains($line, 'connection')) {
                $enhancedLines[] = $line;
                $enhancedLines[] = "Hint: Check your internet connection and try again";
            } else {
                $enhancedLines[] = $line;
            }
        }
        
        return implode("\n", $enhancedLines);
    }
    
    /**
     * Set custom yt-dlp binary path
     */
    public function setYtDlpPath(?string $path): void
    {
        $this->ytDlpPath = $path;
    }
    
    /**
     * Set custom ffmpeg binary path
     */
    public function setFfmpegPath(?string $path): void
    {
        $this->ffmpegPath = $path;
    }
    
    /**
     * Get current working directory
     */
    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }
    
    /**
     * Set working directory
     */
    public function setWorkingDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            throw ConverterException::invalidPath("Working directory does not exist: {$directory}");
        }
        
        $this->workingDirectory = $directory;
    }
    
    /**
     * Get default timeout
     */
    public function getDefaultTimeout(): int
    {
        return $this->defaultTimeout;
    }
    
    /**
     * Set default timeout
     */
    public function setDefaultTimeout(int $timeout): void
    {
        if ($timeout <= 0) {
            throw ConverterException::invalidConfiguration("Timeout must be positive, got: {$timeout}");
        }
        
        $this->defaultTimeout = $timeout;
    }
    
    /**
     * Check if required binaries are available using enhanced detection
     * 
     * Uses the platform-independent fallback mechanism to detect binaries
     * in multiple locations and provides detailed information about each binary.
     * 
     * @return array Detailed information about binary availability
     */
    public function checkBinaries(): array
    {
        $results = [];
        
        // Check yt-dlp
        try {
            $ytDlpPath = PlatformDetector::getExecutablePath('yt-dlp', $this->ytDlpPath);
            $results['yt-dlp'] = [
                'available' => true,
                'path' => $ytDlpPath,
                'location' => $this->determineBinaryLocation($ytDlpPath),
                'custom_path' => $this->ytDlpPath !== null,
                'version' => $this->getBinaryVersion('yt-dlp', $ytDlpPath)
            ];
        } catch (\RuntimeException $e) {
            $results['yt-dlp'] = [
                'available' => false,
                'path' => null,
                'location' => null,
                'custom_path' => $this->ytDlpPath !== null,
                'version' => null,
                'error' => $e->getMessage()
            ];
        }
        
        // Check ffmpeg
        try {
            $ffmpegPath = PlatformDetector::getExecutablePath('ffmpeg', $this->ffmpegPath);
            $results['ffmpeg'] = [
                'available' => true,
                'path' => $ffmpegPath,
                'location' => $this->determineBinaryLocation($ffmpegPath),
                'custom_path' => $this->ffmpegPath !== null,
                'version' => $this->getBinaryVersion('ffmpeg', $ffmpegPath)
            ];
        } catch (\RuntimeException $e) {
            $results['ffmpeg'] = [
                'available' => false,
                'path' => null,
                'location' => null,
                'custom_path' => $this->ffmpegPath !== null,
                'version' => null,
                'error' => $e->getMessage()
            ];
        }
        
        return $results;
    }
    
    /**
     * Determine the location type of a binary path
     * 
     * @param string $path Full path to binary
     * @return string Location description
     */
    private function determineBinaryLocation(string $path): string
    {
        $binPath = PlatformDetector::getBinPath();
        $normalizedPath = $this->normalizeWindowsPath($path);
        $normalizedBinPath = $this->normalizeWindowsPath($binPath);
        
        if (str_starts_with($normalizedPath, $normalizedBinPath)) {
            return 'Project bin/ directory';
        }
        
        // Check if it's in system PATH
        $systemPath = $this->findInSystemPath(basename($path));
        if ($systemPath !== null && $this->normalizeWindowsPath($systemPath) === $normalizedPath) {
            return 'System PATH';
        }
        
        return 'Custom location';
    }
    
    /**
     * Find binary in system PATH (helper method)
     * 
     * @param string $binaryName Name of the binary
     * @return string|null Full path if found, null otherwise
     */
    private function findInSystemPath(string $binaryName): ?string
    {
        // Use 'where' on Windows, 'which' on Unix-like systems
        $command = PlatformDetector::isWindows() ? "where {$binaryName} 2>nul" : "which {$binaryName} 2>/dev/null";
        $output = shell_exec($command);
        
        if ($output !== null) {
            $path = trim($output);
            $lines = explode("\n", $path);
            $firstPath = trim($lines[0]);
            
            if (!empty($firstPath) && file_exists($firstPath)) {
                return $this->normalizeWindowsPath($firstPath);
            }
        }
        
        return null;
    }
    
    /**
     * Get version information for a binary
     * 
     * @param string $binaryName Name of the binary
     * @param string $path Full path to binary
     * @return string|null Version string or null if not available
     */
    private function getBinaryVersion(string $binaryName, string $path): ?string
    {
        try {
            $versionCommands = [
                'ffmpeg' => ['-version'],
                'yt-dlp' => ['--version'],
            ];
            
            if (!isset($versionCommands[$binaryName])) {
                return null;
            }
            
            $command = array_merge([$path], $versionCommands[$binaryName]);
            $process = new Process($command);
            $process->setTimeout(10);
            $process->run();
            
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                $lines = explode("\n", $output);
                
                // Extract version from first line
                if (!empty($lines[0])) {
                    if (preg_match('/(\d+\.\d+(?:\.\d+)?)/', $lines[0], $matches)) {
                        return $matches[1];
                    }
                    
                    // For yt-dlp, the entire first line is usually the version
                    if ($binaryName === 'yt-dlp') {
                        return $lines[0];
                    }
                }
            }
        } catch (\Exception $e) {
            // Version detection failed, but that's not critical
        }
        
        return null;
    }
}