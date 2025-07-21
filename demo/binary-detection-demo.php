<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;
use Darkwob\YoutubeMp3Converter\Converter\Util\ProcessManager;

echo "=== Platform-Independent Binary Detection Demo ===\n\n";

// 1. Show platform detection
echo "1. Platform Detection:\n";
$platform = PlatformDetector::detect();
$platformInfo = PlatformDetector::getPlatformInfo();

echo "   Current Platform: {$platform}\n";
echo "   Is Windows: " . ($platformInfo['is_windows'] ? 'Yes' : 'No') . "\n";
echo "   Is Linux: " . ($platformInfo['is_linux'] ? 'Yes' : 'No') . "\n";
echo "   Is macOS: " . ($platformInfo['is_macos'] ? 'Yes' : 'No') . "\n";
echo "   Directory Separator: '{$platformInfo['directory_separator']}'\n";
echo "   Executable Extension: '{$platformInfo['executable_extension']}'\n";
echo "   Project Root: {$platformInfo['project_root']}\n";
echo "   Bin Path: {$platformInfo['bin_path']}\n\n";

// 2. Show binary filename resolution
echo "2. Binary Filename Resolution:\n";
$binaries = ['yt-dlp', 'ffmpeg', 'custom-tool'];

foreach ($binaries as $binary) {
    $filename = PlatformDetector::getBinaryFilename($binary);
    $fullPath = PlatformDetector::getBinaryPath($binary);
    echo "   {$binary} -> {$filename} (Full path: {$fullPath})\n";
}
echo "\n";

// 3. Show system requirements check
echo "3. System Requirements Check:\n";
$requirements = PlatformDetector::checkRequirements(['yt-dlp', 'ffmpeg']);

foreach ($requirements as $binary => $info) {
    echo "   {$binary}:\n";
    echo "     Exists: " . ($info['exists'] ? 'Yes' : 'No') . "\n";
    
    if ($info['exists']) {
        echo "     Path: {$info['path']}\n";
        echo "     Location: {$info['location']}\n";
        echo "     Version: " . ($info['version'] ?? 'Unknown') . "\n";
    } else {
        echo "     Error: {$info['error']}\n";
        echo "     Installation instructions available: Yes\n";
    }
    echo "\n";
}

// 4. Show ProcessManager integration
echo "4. ProcessManager Integration:\n";
$tempDir = sys_get_temp_dir() . '/demo_' . uniqid();
mkdir($tempDir, 0755, true);

try {
    $processManager = new ProcessManager($tempDir);
    $binaryCheck = $processManager->checkBinaries();
    
    foreach ($binaryCheck as $binary => $info) {
        echo "   {$binary}:\n";
        echo "     Available: " . ($info['available'] ? 'Yes' : 'No') . "\n";
        echo "     Custom Path: " . ($info['custom_path'] ? 'Yes' : 'No') . "\n";
        
        if ($info['available']) {
            echo "     Path: {$info['path']}\n";
            echo "     Location: {$info['location']}\n";
            echo "     Version: " . ($info['version'] ?? 'Unknown') . "\n";
        } else {
            echo "     Error: {$info['error']}\n";
        }
        echo "\n";
    }
} finally {
    // Cleanup
    if (is_dir($tempDir)) {
        rmdir($tempDir);
    }
}

// 5. Show fallback mechanism in action
echo "5. Fallback Mechanism Demo:\n";
echo "   Trying to find non-existent binary 'demo-binary'...\n";

try {
    $path = PlatformDetector::getExecutablePath('demo-binary');
    echo "   Found: {$path}\n";
} catch (\RuntimeException $e) {
    echo "   Not found (as expected)\n";
    echo "   Error message shows multiple strategies tried:\n";
    
    $lines = explode("\n", $e->getMessage());
    foreach ($lines as $line) {
        if (!empty(trim($line))) {
            echo "     " . trim($line) . "\n";
        }
    }
}

echo "\n=== Demo Complete ===\n";