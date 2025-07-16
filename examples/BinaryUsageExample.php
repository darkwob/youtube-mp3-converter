<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;

echo "=== Binary Helper Usage Examples ===\n\n";

// Example 1: Automatic binary detection (no custom path)
echo "1. Automatic Binary Detection:\n";
try {
    // Look for yt-dlp automatically in bin/ directory
    $ytdlpPath = PlatformDetector::getExecutablePath('yt-dlp');
    echo "   ✓ yt-dlp found: {$ytdlpPath}\n";
    
    // Look for ffmpeg automatically
    $ffmpegPath = PlatformDetector::getExecutablePath('ffmpeg');
    echo "   ✓ ffmpeg found: {$ffmpegPath}\n";
    
} catch (\RuntimeException $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Example 2: Using custom paths
echo "2. Custom Binary Paths:\n";
try {
    // User provides custom filename in bin directory
    $customPath1 = PlatformDetector::getExecutablePath('yt-dlp', 'yt-dlp-custom.exe');
    echo "   ✓ Custom filename: {$customPath1}\n";
    
} catch (\RuntimeException $e) {
    echo "   ℹ Custom filename not found (expected): " . explode("\n", $e->getMessage())[0] . "\n";
}

try {
    // User provides full path
    $customPath2 = PlatformDetector::getExecutablePath('ffmpeg', '/usr/local/bin/ffmpeg');
    echo "   ✓ Full path: {$customPath2}\n";
    
} catch (\RuntimeException $e) {
    echo "   ℹ Full path not found (expected): " . explode("\n", $e->getMessage())[0] . "\n";
}

try {
    // User provides relative path
    $customPath3 = PlatformDetector::getExecutablePath('yt-dlp', './tools/yt-dlp');
    echo "   ✓ Relative path: {$customPath3}\n";
    
} catch (\RuntimeException $e) {
    echo "   ℹ Relative path not found (expected): " . explode("\n", $e->getMessage())[0] . "\n";
}
echo "\n";

// Example 3: Convenience methods with custom paths
echo "3. Convenience Methods with Custom Paths:\n";

// Check if binary exists
$exists1 = PlatformDetector::binaryExists('yt-dlp'); // Auto detection
$exists2 = PlatformDetector::binaryExists('yt-dlp', '/custom/path/yt-dlp'); // Custom path
echo "   Auto detection exists: " . ($exists1 ? 'Yes' : 'No') . "\n";
echo "   Custom path exists: " . ($exists2 ? 'Yes' : 'No') . "\n";

// Execute binary
if ($exists1) {
    echo "\n   Executing yt-dlp with auto detection:\n";
    $result1 = PlatformDetector::executeBinary('yt-dlp', ['--version']);
    if ($result1['success']) {
        echo "     Version: " . trim($result1['output'][0] ?? 'Unknown') . "\n";
        echo "     Binary used: {$result1['binary_path']}\n";
    }
}

// Create command array for proc_open
if ($exists1) {
    echo "\n   Command array for proc_open():\n";
    $command1 = PlatformDetector::createCommand('yt-dlp', ['--help']);
    echo "     " . json_encode($command1, JSON_UNESCAPED_SLASHES) . "\n";
    
    // With custom path
    try {
        $command2 = PlatformDetector::createCommand('ffmpeg', ['-version'], '/usr/bin/ffmpeg');
        echo "     " . json_encode($command2, JSON_UNESCAPED_SLASHES) . "\n";
    } catch (\RuntimeException $e) {
        echo "     Custom ffmpeg path not available\n";
    }
}
echo "\n";

// Example 4: Real usage scenarios
echo "4. Real Usage Scenarios:\n";

// Scenario A: User has binaries in standard location
echo "   Scenario A - Standard location:\n";
try {
    $ytdlp = PlatformDetector::getExecutablePath('yt-dlp');
    echo "     Ready to use: {$ytdlp}\n";
    echo "     Usage: proc_open(['{$ytdlp}', '--help'], ...)\n";
} catch (\RuntimeException $e) {
    echo "     " . explode("\n", $e->getMessage())[0] . "\n";
}

// Scenario B: User has custom installation
echo "\n   Scenario B - Custom installation:\n";
$customBinaries = [
    'yt-dlp' => '/opt/youtube-dl/yt-dlp',
    'ffmpeg' => 'C:\\Tools\\ffmpeg\\bin\\ffmpeg.exe',
    'yt-dlp' => './my-tools/yt-dlp'
];

foreach ($customBinaries as $name => $path) {
    try {
        $resolvedPath = PlatformDetector::getExecutablePath($name, $path);
        echo "     ✓ {$name}: {$resolvedPath}\n";
    } catch (\RuntimeException $e) {
        echo "     ✗ {$name} at {$path}: Not found\n";
    }
}

// Scenario C: Flexible function for user's choice
echo "\n   Scenario C - User's choice function:\n";

function getYtDlpPath(?string $userPath = null): string {
    return PlatformDetector::getExecutablePath('yt-dlp', $userPath);
}

function getFfmpegPath(?string $userPath = null): string {
    return PlatformDetector::getExecutablePath('ffmpeg', $userPath);
}

// User can call these functions with or without custom paths
try {
    echo "     Auto yt-dlp: " . getYtDlpPath() . "\n";
    echo "     Custom yt-dlp: " . getYtDlpPath('/custom/yt-dlp') . "\n";
} catch (\RuntimeException $e) {
    echo "     Error: " . explode("\n", $e->getMessage())[0] . "\n";
}

echo "\n";

// Example 5: Platform information
echo "5. Platform Information:\n";
$info = PlatformDetector::getPlatformInfo();
foreach ($info as $key => $value) {
    $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
    echo "   {$key}: {$displayValue}\n";
}

echo "\n=== Complete! ===\n";
echo "\nKey takeaways:\n";
echo "- Use getExecutablePath() as the main helper function\n";
echo "- Pass null (default) for automatic detection in bin/\n";
echo "- Pass custom path/filename for user-provided binaries\n";
echo "- All paths are validated and normalized automatically\n";
echo "- Works cross-platform (Windows/Linux/macOS)\n";
echo "- Returns full paths ready for proc_open() or shell_exec()\n"; 