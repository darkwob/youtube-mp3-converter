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
     * Get executable path for a binary with platform-independent fallback mechanism
     * 
     * This is the main helper function that handles the logic:
     * 1. If custom path provided, validate and return it
     * 2. If no path provided, look in bin/ directory with fallback mechanism
     * 3. Try multiple naming conventions and locations
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
        
        return self::findBinaryWithFallback($binaryName);
    }

    /**
     * Validate and return custom path provided by user with enhanced handling
     * 
     * Supports:
     * - Full absolute paths
     * - Relative paths
     * - Just filenames (searches in bin directory)
     * - Platform-independent naming (tries with and without extensions)
     * 
     * @param string $customPath User-provided path
     * @return string Validated full path to executable
     * @throws \RuntimeException If binary not found or not executable
     */
    private static function validateCustomPath(string $customPath): string
    {
        $pathsToTry = [];
        
        // If it's just a filename, look in bin directory
        if (basename($customPath) === $customPath) {
            $binPath = self::getBinPath();
            
            // Try the exact filename provided
            $pathsToTry[] = $binPath . DIRECTORY_SEPARATOR . $customPath;
            
            // Try with platform-specific extension if not already present
            $withExtension = self::getBinaryFilename($customPath);
            if ($withExtension !== $customPath) {
                $pathsToTry[] = $binPath . DIRECTORY_SEPARATOR . $withExtension;
            }
            
            // Try without extension if it has one
            $withoutExtension = pathinfo($customPath, PATHINFO_FILENAME);
            if ($withoutExtension !== $customPath) {
                $pathsToTry[] = $binPath . DIRECTORY_SEPARATOR . $withoutExtension;
            }
        } else {
            // It's a full or relative path
            $pathsToTry[] = $customPath;
            
            // Try with platform-specific extension if not already present
            $withExtension = self::getBinaryFilename($customPath);
            if ($withExtension !== $customPath) {
                $pathsToTry[] = $withExtension;
            }
            
            // Try without extension if it has one
            $extension = pathinfo($customPath, PATHINFO_EXTENSION);
            if (!empty($extension)) {
                $withoutExtension = pathinfo($customPath, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . 
                                   pathinfo($customPath, PATHINFO_FILENAME);
                $pathsToTry[] = $withoutExtension;
            }
        }
        
        // Remove duplicates and normalize paths
        $pathsToTry = array_unique($pathsToTry);
        $normalizedPaths = array_map([self::class, 'normalizePath'], $pathsToTry);
        
        // Try each path
        foreach ($normalizedPaths as $normalizedPath) {
            if (file_exists($normalizedPath)) {
                if (!is_executable($normalizedPath)) {
                    $fixInstructions = self::isWindows() 
                        ? "Check file permissions and ensure it's a valid executable."
                        : "Fix with: chmod +x {$normalizedPath}";
                        
                    throw new \RuntimeException(
                        "Custom binary exists but is not executable: {$normalizedPath}\n{$fixInstructions}"
                    );
                }
                
                return $normalizedPath;
            }
        }
        
        // None of the paths worked
        $attemptsList = implode("\n  - ", $normalizedPaths);
        throw new \RuntimeException(
            "Custom binary not found. Tried the following paths:\n  - {$attemptsList}\n\n" .
            "Please ensure the binary exists and is accessible."
        );
    }

    /**
     * Find binary with platform-independent fallback mechanism
     * 
     * Tries multiple naming conventions and locations:
     * 1. Platform-specific name in bin/ directory
     * 2. Base name without extension in bin/ directory
     * 3. System PATH lookup
     * 4. Common installation locations
     * 
     * @param string $binaryName Name of the binary
     * @return string Full path to executable
     * @throws \RuntimeException If binary not found anywhere
     */
    private static function findBinaryWithFallback(string $binaryName): string
    {
        $binPath = self::getBinPath();
        $attempts = [];
        
        // Strategy 1: Try platform-specific name in bin/ directory
        $platformSpecificName = self::getBinaryFilename($binaryName);
        $platformSpecificPath = $binPath . DIRECTORY_SEPARATOR . $platformSpecificName;
        $normalizedPlatformPath = self::normalizePath($platformSpecificPath);
        $attempts[] = ['path' => $normalizedPlatformPath, 'strategy' => 'platform-specific in bin/'];
        
        if (file_exists($normalizedPlatformPath) && is_executable($normalizedPlatformPath)) {
            return $normalizedPlatformPath;
        }
        
        // Strategy 2: Try base name without extension in bin/ directory
        $basePath = $binPath . DIRECTORY_SEPARATOR . $binaryName;
        $normalizedBasePath = self::normalizePath($basePath);
        $attempts[] = ['path' => $normalizedBasePath, 'strategy' => 'base name in bin/'];
        
        if (file_exists($normalizedBasePath) && is_executable($normalizedBasePath)) {
            return $normalizedBasePath;
        }
        
        // Strategy 3: Try system PATH lookup
        $systemPath = self::findInSystemPath($binaryName);
        if ($systemPath !== null) {
            $attempts[] = ['path' => $systemPath, 'strategy' => 'system PATH'];
            return $systemPath;
        }
        
        // Strategy 4: Try common installation locations
        $commonPath = self::findInCommonLocations($binaryName);
        if ($commonPath !== null) {
            $attempts[] = ['path' => $commonPath, 'strategy' => 'common locations'];
            return $commonPath;
        }
        
        // All strategies failed - provide comprehensive error message
        $instructions = self::getInstallationInstructions($binaryName);
        $attemptsList = implode("\n", array_map(fn($attempt) => "  - {$attempt['strategy']}: {$attempt['path']}", $attempts));
        
        throw new \RuntimeException(
            "Binary '{$binaryName}' not found in any of the following locations:\n{$attemptsList}\n\n" .
            "Installation instructions:\n{$instructions}"
        );
    }
    
    /**
     * Find binary in system PATH
     * 
     * @param string $binaryName Name of the binary
     * @return string|null Full path if found, null otherwise
     */
    private static function findInSystemPath(string $binaryName): ?string
    {
        $possibleNames = self::getPossibleBinaryNames($binaryName);
        
        foreach ($possibleNames as $name) {
            // Use 'where' on Windows, 'which' on Unix-like systems
            $command = self::isWindows() ? "where {$name} 2>nul" : "which {$name} 2>/dev/null";
            $output = shell_exec($command);
            
            if ($output !== null) {
                $path = trim($output);
                $lines = explode("\n", $path);
                $firstPath = trim($lines[0]);
                
                if (!empty($firstPath) && file_exists($firstPath) && is_executable($firstPath)) {
                    return self::normalizePath($firstPath);
                }
            }
        }
        
        return null;
    }
    
    /**
     * Find binary in common installation locations
     * 
     * @param string $binaryName Name of the binary
     * @return string|null Full path if found, null otherwise
     */
    private static function findInCommonLocations(string $binaryName): ?string
    {
        $commonLocations = self::getCommonInstallationPaths($binaryName);
        $possibleNames = self::getPossibleBinaryNames($binaryName);
        
        foreach ($commonLocations as $location) {
            if (!is_dir($location)) {
                continue;
            }
            
            foreach ($possibleNames as $name) {
                $fullPath = self::normalizePath($location . DIRECTORY_SEPARATOR . $name);
                
                if (file_exists($fullPath) && is_executable($fullPath)) {
                    return $fullPath;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get all possible names for a binary (with and without extensions)
     * 
     * @param string $binaryName Base binary name
     * @return array Array of possible names to try
     */
    private static function getPossibleBinaryNames(string $binaryName): array
    {
        $names = [$binaryName];
        
        // Add platform-specific extension if not already present
        $platformSpecific = self::getBinaryFilename($binaryName);
        if ($platformSpecific !== $binaryName) {
            $names[] = $platformSpecific;
        }
        
        // On Windows, also try common executable extensions
        if (self::isWindows()) {
            $extensions = ['.exe', '.bat', '.cmd', '.com'];
            foreach ($extensions as $ext) {
                if (!str_ends_with($binaryName, $ext)) {
                    $names[] = $binaryName . $ext;
                }
            }
        }
        
        return array_unique($names);
    }
    
    /**
     * Get common installation paths for a binary based on platform
     * 
     * @param string $binaryName Name of the binary
     * @return array Array of common installation directories
     */
    private static function getCommonInstallationPaths(string $binaryName): array
    {
        $platform = self::detect();
        $commonPaths = [];
        
        switch ($platform) {
            case self::WINDOWS:
                $commonPaths = [
                    'C:\\ffmpeg\\bin',
                    'C:\\Program Files\\ffmpeg\\bin',
                    'C:\\Program Files (x86)\\ffmpeg\\bin',
                    'C:\\yt-dlp',
                    'C:\\Program Files\\yt-dlp',
                    'C:\\Program Files (x86)\\yt-dlp',
                    'C:\\ProgramData\\chocolatey\\bin',
                    'C:\\tools\\ffmpeg\\bin',
                    'C:\\tools\\yt-dlp',
                ];
                
                // Add user-specific paths
                $userProfile = $_ENV['USERPROFILE'] ?? null;
                if ($userProfile) {
                    $commonPaths = array_merge($commonPaths, [
                        $userProfile . '\\scoop\\shims',
                        $userProfile . '\\AppData\\Local\\bin',
                        $userProfile . '\\AppData\\Local\\Programs\\ffmpeg\\bin',
                        $userProfile . '\\AppData\\Local\\Programs\\yt-dlp',
                    ]);
                }
                break;
                
            case self::LINUX:
                $commonPaths = [
                    '/usr/local/bin',
                    '/usr/bin',
                    '/bin',
                    '/opt/ffmpeg/bin',
                    '/opt/yt-dlp/bin',
                    '/snap/bin',
                ];
                
                // Add user-specific paths
                $home = $_ENV['HOME'] ?? null;
                if ($home) {
                    $commonPaths = array_merge($commonPaths, [
                        $home . '/.local/bin',
                        $home . '/bin',
                        $home . '/.cargo/bin',
                    ]);
                }
                break;
                
            case self::MACOS:
                $commonPaths = [
                    '/usr/local/bin',
                    '/usr/bin',
                    '/bin',
                    '/opt/homebrew/bin',
                    '/usr/local/Cellar/ffmpeg/*/bin',
                    '/usr/local/Cellar/yt-dlp/*/bin',
                ];
                
                // Add user-specific paths
                $home = $_ENV['HOME'] ?? null;
                if ($home) {
                    $commonPaths = array_merge($commonPaths, [
                        $home . '/.local/bin',
                        $home . '/bin',
                        $home . '/.cargo/bin',
                    ]);
                }
                break;
        }
        
        // Filter to only existing directories
        return array_filter($commonPaths, 'is_dir');
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
     * Get platform-specific binary filename with fallback support
     * 
     * This method provides the most likely filename for a binary on the current platform,
     * but the actual binary resolution uses fallback mechanisms to try multiple variants.
     * 
     * @param string $binaryName Base binary name
     * @return string Most likely filename for the current platform
     */
    public static function getBinaryFilename(string $binaryName): string
    {
        // If the binary name already has an extension, return as-is
        if (pathinfo($binaryName, PATHINFO_EXTENSION) !== '') {
            return $binaryName;
        }
        
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
     * Get comprehensive installation instructions for a binary
     * 
     * Provides multiple installation options including:
     * - Manual installation in bin/ directory
     * - System package managers
     * - Direct system installation
     * 
     * @param string $binaryName Name of the binary
     * @return string Detailed installation instructions
     */
    public static function getInstallationInstructions(string $binaryName): string
    {
        $binPath = self::getBinPath();
        $filename = self::getBinaryFilename($binaryName);
        $downloadInfo = self::getBinaryDownloadInfo($binaryName);
        $currentPlatform = self::detect();
        
        $instructions = "To install {$binaryName}, you have several options:\n\n";
        
        // Option 1: Manual installation in bin/ directory
        $instructions .= "OPTION 1: Manual installation in project bin/ directory\n";
        $instructions .= "1. Create bin directory: {$binPath}\n";
        
        if (isset($downloadInfo[$currentPlatform])) {
            $downloadUrl = $downloadInfo[$currentPlatform];
            $instructions .= "2. Download from: {$downloadUrl}\n";
            $instructions .= "3. Extract and place the executable as: {$binPath}" . DIRECTORY_SEPARATOR . "{$filename}\n";
            
            if (!self::isWindows()) {
                $instructions .= "4. Make executable: chmod +x {$binPath}" . DIRECTORY_SEPARATOR . "{$filename}\n";
            }
        } else {
            $instructions .= "2. Download the appropriate binary for your platform\n";
            $instructions .= "3. Place it as: {$binPath}" . DIRECTORY_SEPARATOR . "{$filename}\n";
            
            if (!self::isWindows()) {
                $instructions .= "4. Make executable: chmod +x {$binPath}" . DIRECTORY_SEPARATOR . "{$filename}\n";
            }
        }
        
        // Option 2: System installation
        $instructions .= "\nOPTION 2: System-wide installation\n";
        $instructions .= self::getSystemInstallationInstructions($binaryName);
        
        // Option 3: Package managers
        $instructions .= "\nOPTION 3: Package managers\n";
        $instructions .= self::getPackageManagerInstructions($binaryName);
        
        // Additional notes
        $instructions .= "\nNOTES:\n";
        $instructions .= "- The system will automatically detect the binary in any of these locations\n";
        $instructions .= "- You can also specify a custom path when creating the converter\n";
        $instructions .= "- Make sure the binary is executable and accessible\n";
        
        return $instructions;
    }
    
    /**
     * Get system installation instructions for a binary
     * 
     * @param string $binaryName Name of the binary
     * @return string System installation instructions
     */
    private static function getSystemInstallationInstructions(string $binaryName): string
    {
        $platform = self::detect();
        
        switch ($platform) {
            case self::WINDOWS:
                return "- Download and run the installer from the official website\n" .
                       "- Add the installation directory to your system PATH\n" .
                       "- Restart your command prompt/IDE after installation\n";
                       
            case self::LINUX:
                $instructions = "- Install via system package manager:\n";
                if ($binaryName === 'ffmpeg') {
                    $instructions .= "  sudo apt install ffmpeg (Ubuntu/Debian)\n";
                    $instructions .= "  sudo yum install ffmpeg (CentOS/RHEL)\n";
                    $instructions .= "  sudo pacman -S ffmpeg (Arch Linux)\n";
                } elseif ($binaryName === 'yt-dlp') {
                    $instructions .= "  sudo apt install yt-dlp (Ubuntu 22.04+)\n";
                    $instructions .= "  pip install yt-dlp (via Python pip)\n";
                    $instructions .= "  sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp\n";
                    $instructions .= "  sudo chmod +x /usr/local/bin/yt-dlp\n";
                }
                return $instructions;
                
            case self::MACOS:
                $instructions = "- Install via Homebrew:\n";
                if ($binaryName === 'ffmpeg') {
                    $instructions .= "  brew install ffmpeg\n";
                } elseif ($binaryName === 'yt-dlp') {
                    $instructions .= "  brew install yt-dlp\n";
                }
                $instructions .= "- Or install via MacPorts:\n";
                $instructions .= "  sudo port install {$binaryName}\n";
                return $instructions;
                
            default:
                return "- Install using your system's package manager\n" .
                       "- Or download and install manually to a directory in your PATH\n";
        }
    }
    
    /**
     * Get package manager installation instructions
     * 
     * @param string $binaryName Name of the binary
     * @return string Package manager instructions
     */
    private static function getPackageManagerInstructions(string $binaryName): string
    {
        $platform = self::detect();
        $instructions = "";
        
        switch ($platform) {
            case self::WINDOWS:
                $instructions .= "- Chocolatey: choco install {$binaryName}\n";
                $instructions .= "- Scoop: scoop install {$binaryName}\n";
                $instructions .= "- Winget: winget install {$binaryName}\n";
                break;
                
            case self::LINUX:
                $instructions .= "- Snap: sudo snap install {$binaryName}\n";
                $instructions .= "- Flatpak: flatpak install {$binaryName}\n";
                $instructions .= "- AppImage: Download from official releases\n";
                break;
                
            case self::MACOS:
                $instructions .= "- Homebrew: brew install {$binaryName}\n";
                $instructions .= "- MacPorts: sudo port install {$binaryName}\n";
                break;
        }
        
        if ($binaryName === 'yt-dlp') {
            $instructions .= "- Python pip: pip install yt-dlp\n";
            $instructions .= "- pipx: pipx install yt-dlp\n";
        }
        
        return $instructions;
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
     * Check system requirements and provide setup instructions with enhanced detection
     * 
     * Uses the fallback mechanism to detect binaries in multiple locations
     * and provides comprehensive setup instructions if not found.
     * 
     * @param array $requiredBinaries List of required binary names
     * @return array Detailed results for each binary
     */
    public static function checkRequirements(array $requiredBinaries = ['ffmpeg', 'yt-dlp']): array
    {
        $results = [];
        
        foreach ($requiredBinaries as $binary) {
            try {
                $path = self::getExecutablePath($binary);
                $results[$binary] = [
                    'exists' => true,
                    'path' => $path,
                    'location' => self::determineBinaryLocation($path),
                    'version' => self::getBinaryVersion($binary, $path),
                    'instructions' => null
                ];
            } catch (\RuntimeException $e) {
                $results[$binary] = [
                    'exists' => false,
                    'path' => null,
                    'location' => null,
                    'version' => null,
                    'error' => $e->getMessage(),
                    'instructions' => self::getInstallationInstructions($binary)
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Determine the location type of a binary path
     * 
     * @param string $path Full path to binary
     * @return string Location type (bin/, system, custom)
     */
    private static function determineBinaryLocation(string $path): string
    {
        $binPath = self::getBinPath();
        $normalizedPath = self::normalizePath($path);
        $normalizedBinPath = self::normalizePath($binPath);
        
        if (str_starts_with($normalizedPath, $normalizedBinPath)) {
            return 'project bin/';
        }
        
        // Check common system paths
        $systemPaths = ['/usr/bin', '/usr/local/bin', '/bin', 'C:\\Windows\\System32'];
        foreach ($systemPaths as $systemPath) {
            $normalizedSystemPath = self::normalizePath($systemPath);
            if (str_starts_with($normalizedPath, $normalizedSystemPath)) {
                return 'system';
            }
        }
        
        return 'custom location';
    }
    
    /**
     * Get version information for a binary
     * 
     * @param string $binaryName Name of the binary
     * @param string $path Full path to binary
     * @return string|null Version string or null if not available
     */
    private static function getBinaryVersion(string $binaryName, string $path): ?string
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
            $process = new \Symfony\Component\Process\Process($command);
            $process->setTimeout(10);
            $process->run();
            
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                $lines = explode("\n", $output);
                
                // Extract version from first line for most binaries
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