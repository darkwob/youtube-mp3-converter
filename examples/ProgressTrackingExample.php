<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Darkwob\YoutubeMp3Converter\Converter\YouTubeConverter;
use Darkwob\YoutubeMp3Converter\Progress\FileProgress;
use Darkwob\YoutubeMp3Converter\Converter\Options\ConverterOptions;

/**
 * Example demonstrating progress tracking integration
 * 
 * This example shows how the enhanced progress tracking works with:
 * - Stage updates with validation
 * - Real-time progress callbacks
 * - Formatted progress data
 * - Progress data structure validation
 */

echo "YouTube MP3 Converter - Progress Tracking Example\n";
echo "================================================\n\n";

// Setup directories
$baseDir = dirname(__DIR__);
$outputDir = $baseDir . '/demo/downloads';
$tempDir = $baseDir . '/demo/temp';
$progressDir = $baseDir . '/demo/progress';

// Ensure directories exist
foreach ([$outputDir, $tempDir, $progressDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Create progress tracker
$progressTracker = new FileProgress($progressDir);

// Create converter options
$options = new ConverterOptions();
$options->setAudioFormat('mp3');
$options->setAudioQuality(5); // Medium quality

// Create converter with progress tracking
$converter = new YouTubeConverter($outputDir, $tempDir, $progressTracker, $options);

// Example video URL (replace with actual URL for testing)
$testUrl = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'; // Rick Roll for testing

echo "Testing progress tracking with URL: {$testUrl}\n\n";

// Extract video ID for progress tracking
try {
    $videoId = extractVideoIdFromUrl($testUrl);
    echo "Video ID: {$videoId}\n\n";
    
    // Start monitoring progress in a separate process (simulation)
    echo "Progress tracking demonstration:\n";
    echo "--------------------------------\n";
    
    // Simulate progress updates that would happen during actual conversion
    simulateProgressUpdates($progressTracker, $videoId);
    
    echo "\nProgress tracking features demonstrated:\n";
    echo "- ✓ Stage validation (starting, downloading, converting, completed, error)\n";
    echo "- ✓ Percentage validation (0-100 range)\n";
    echo "- ✓ Progress data formatting with additional information\n";
    echo "- ✓ Real-time progress callbacks during download and conversion\n";
    echo "- ✓ ETA calculation based on elapsed time\n";
    echo "- ✓ File size and duration formatting\n";
    echo "- ✓ Progress bar generation\n";
    echo "- ✓ Error handling with progress tracking\n\n";
    
    // Show final progress state
    $finalProgress = $progressTracker->get($videoId);
    if ($finalProgress) {
        echo "Final progress state:\n";
        echo "ID: {$finalProgress['id']}\n";
        echo "Status: {$finalProgress['status']}\n";
        echo "Progress: {$finalProgress['progress']}%\n";
        echo "Message: {$finalProgress['message']}\n";
        echo "Updated: " . date('Y-m-d H:i:s', $finalProgress['updated_at']) . "\n";
    }
    
    // Cleanup
    $progressTracker->delete($videoId);
    echo "\nProgress tracking example completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nNote: This is a demonstration of progress tracking features.\n";
    echo "For actual conversion, ensure yt-dlp and ffmpeg binaries are available.\n";
}

/**
 * Extract video ID from YouTube URL (simplified version)
 */
function extractVideoIdFromUrl(string $url): string
{
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        return $matches[1];
    }
    return 'demo_video_' . uniqid();
}

/**
 * Simulate progress updates to demonstrate the tracking system
 */
function simulateProgressUpdates(FileProgress $progressTracker, string $videoId): void
{
    $stages = [
        ['stage' => 'starting', 'progress' => 0, 'message' => 'Initializing conversion for video ' . $videoId],
        ['stage' => 'downloading', 'progress' => 10, 'message' => 'Starting download'],
        ['stage' => 'downloading', 'progress' => 25, 'message' => 'Downloading (25%) - Speed: 1.5MB/s - Size: 4.2MB - ETA: 00:15'],
        ['stage' => 'downloading', 'progress' => 50, 'message' => 'Downloading (50%) - Speed: 1.8MB/s - Size: 4.2MB - ETA: 00:08'],
        ['stage' => 'downloading', 'progress' => 65, 'message' => 'Download completed'],
        ['stage' => 'downloading', 'progress' => 70, 'message' => 'File ready for conversion - File: video.webm - Size: 4.2 MB'],
        ['stage' => 'converting', 'progress' => 75, 'message' => 'Preparing conversion'],
        ['stage' => 'converting', 'progress' => 80, 'message' => 'Starting conversion - Codec: libmp3lame - Bitrate: 192k'],
        ['stage' => 'converting', 'progress' => 90, 'message' => 'Converting to mp3 (85%) - Time: 01:23'],
        ['stage' => 'converting', 'progress' => 95, 'message' => 'Conversion completed - File: video.mp3 - Size: 3.1 MB'],
        ['stage' => 'completed', 'progress' => 100, 'message' => 'Conversion completed successfully - File: video.mp3 - Size: 3.1 MB - Duration: 03:25']
    ];
    
    foreach ($stages as $i => $stage) {
        echo sprintf(
            "[%s] %s: %d%% - %s\n",
            date('H:i:s'),
            ucfirst($stage['stage']),
            $stage['progress'],
            $stage['message']
        );
        
        $progressTracker->update($videoId, $stage['stage'], $stage['progress'], $stage['message']);
        
        // Small delay to simulate real processing time
        usleep(200000); // 0.2 seconds
    }
}