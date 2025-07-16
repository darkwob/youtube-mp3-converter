<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Darkwob\YoutubeMp3Converter\Converter\Util\PlatformDetector;

echo "=== Platform Binary Management Example ===\n\n";

// Platform bilgilerini göster
echo "Platform Information:\n";
$platformInfo = PlatformDetector::getPlatformInfo();
foreach ($platformInfo as $key => $value) {
    echo "  {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
}
echo "\n";

// Gerekli binary'leri kontrol et
echo "Checking Required Binaries:\n";
$requirements = PlatformDetector::checkRequirements(['ffmpeg', 'yt-dlp']);

foreach ($requirements as $binary => $info) {
    echo "  {$binary}:\n";
    echo "    Status: " . ($info['exists'] ? 'FOUND' : 'NOT FOUND') . "\n";
    
    if ($info['exists']) {
        echo "    Path: {$info['path']}\n";
    } else {
        echo "    Installation Instructions:\n";
        $instructions = explode("\n", $info['instructions']);
        foreach ($instructions as $instruction) {
            echo "      {$instruction}\n";
        }
    }
    echo "\n";
}

// Binary dosya isimlerini göster
echo "Expected Binary Filenames:\n";
foreach (['ffmpeg', 'yt-dlp'] as $binary) {
    $filename = PlatformDetector::getBinaryFilename($binary);
    $fullPath = PlatformDetector::getBinaryPath($binary);
    echo "  {$binary} -> {$filename}\n";
    echo "    Full path: {$fullPath}\n";
}
echo "\n";

// Bin klasörünü oluştur
echo "Creating bin directory if needed...\n";
if (PlatformDetector::createBinDirectory()) {
    echo "  ✓ Bin directory ready: " . PlatformDetector::getBinPath() . "\n";
} else {
    echo "  ✗ Failed to create bin directory\n";
}
echo "\n";

// Örnek kullanım - sadece binary varsa
echo "Example Usage (if binaries exist):\n";

try {
    // yt-dlp ile video bilgisi al
    if (PlatformDetector::binaryExists('yt-dlp')) {
        echo "  Testing yt-dlp...\n";
        $result = PlatformDetector::executeBinary('yt-dlp', ['--version']);
        
        if ($result['success']) {
            echo "    ✓ yt-dlp version: " . trim($result['output'][0] ?? 'Unknown') . "\n";
        } else {
            echo "    ✗ yt-dlp failed with return code: {$result['return_code']}\n";
        }
    } else {
        echo "  yt-dlp not found - skipping test\n";
    }
    
    // ffmpeg versiyonu kontrol et
    if (PlatformDetector::binaryExists('ffmpeg')) {
        echo "  Testing ffmpeg...\n";
        $result = PlatformDetector::executeBinary('ffmpeg', ['-version']);
        
        if ($result['success']) {
            // ffmpeg'in ilk satırını al
            $versionLine = $result['output'][0] ?? 'Unknown';
            echo "    ✓ ffmpeg: " . trim($versionLine) . "\n";
        } else {
            echo "    ✗ ffmpeg failed with return code: {$result['return_code']}\n";
        }
    } else {
        echo "  ffmpeg not found - skipping test\n";
    }
    
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Symfony Process kullanımı örneği
echo "Symfony Process Integration Example:\n";
if (class_exists('Symfony\Component\Process\Process')) {
    echo "  Symfony Process is available\n";
    
    // Komut dizisi oluştur
    try {
        $command = PlatformDetector::createCommand('yt-dlp', ['--help']);
        echo "  Command array: " . json_encode($command, JSON_UNESCAPED_SLASHES) . "\n";
        
        // Process oluştur (örnekte sadece komut gösteriyoruz)
        echo "  Usage: \$process = new Process(" . json_encode($command) . ");\n";
        echo "         \$process->run();\n";
        
    } catch (Exception $e) {
        echo "  Note: " . $e->getMessage() . "\n";
    }
} else {
    echo "  Symfony Process not available\n";
}

echo "\nDone!\n"; 